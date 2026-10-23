<?php

declare(strict_types=1);

namespace App\Tests\Loan;

use App\Loan\LoanCalculator;
use App\Loan\LoanType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Контрольные значения те же, что во фронтенд-тестах (src/entities/loan/model/__tests__/credit.test.ts) */
final class LoanCalculatorTest extends TestCase
{
    /** @return iterable<string, array{int, LoanType, int, float}> */
    public static function paymentCases(): iterable
    {
        yield 'ипотека 18%' => [1_000_000, LoanType::Mortgage, 60, 25_393.43];
        yield 'автокредит 23.5%' => [1_000_000, LoanType::Auto, 60, 28_478.48];
        yield 'потребительский 22.2%' => [1_000_000, LoanType::Consumer, 60, 27_732.75];
        yield 'до зарплаты 50%' => [50_000, LoanType::Payday, 12, 5_379.26];
    }

    #[DataProvider('paymentCases')]
    public function testAnnuityPayment(int $amount, LoanType $type, int $term, float $expected): void
    {
        self::assertSame($expected, LoanCalculator::annuityPayment($amount, $type->product()->annualRate, $term));
    }

    public function testOneMonthTermIsAmountPlusOneMonthInterest(): void
    {
        self::assertSame(101_500.0, LoanCalculator::annuityPayment(100_000, 0.18, 1));
    }

    public function testZeroRateSplitsEvenly(): void
    {
        self::assertSame(10_000.0, LoanCalculator::annuityPayment(120_000, 0.0, 12));
    }

    public function testRejectsNonPositiveAmountOrTerm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LoanCalculator::annuityPayment(0, 0.18, 12);
    }

    public function testRejectsZeroTerm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LoanCalculator::annuityPayment(100_000, 0.18, 0);
    }

    public function testFullCalculationForMortgage(): void
    {
        $calc = LoanCalculator::calculate(LoanType::Mortgage, 5_000_000, 120);

        self::assertSame(90_092.6, $calc->monthlyPayment);
        self::assertSame(120, $calc->paymentsCount);
        self::assertSame(10_811_112.0, $calc->totalRepaid);
        self::assertSame(5_811_112.0, $calc->totalInterest);
        // 90 092.60 / 0.5 = 180 185.20 -> вверх до сотен
        self::assertSame(180_200, $calc->requiredIncome);
    }

    public function testRequiredIncomeRoundsUpToHundreds(): void
    {
        $calc = LoanCalculator::calculate(LoanType::Payday, 20_000, 2);

        // платёж 10 629.25 -> /0.5 = 21 258.50 -> 21 300
        self::assertSame(10_629.25, $calc->monthlyPayment);
        self::assertSame(21_300, $calc->requiredIncome);
    }

    public function testRound2MatchesJavaScriptMathRound(): void
    {
        // Math.round(1.005 * 100) / 100 === 1 в JS (1.005 в float чуть меньше), PHP round(1.005, 2) дал бы 1.01
        self::assertSame(1.0, LoanCalculator::round2(1.005));
        self::assertSame(2.68, LoanCalculator::round2(2.675));
        self::assertSame(0.13, LoanCalculator::round2(0.125));
        self::assertSame(1.25, LoanCalculator::round2(1.255));
        self::assertSame(10.07, LoanCalculator::round2(10.075));
    }
}
