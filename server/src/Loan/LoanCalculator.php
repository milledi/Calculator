<?php

declare(strict_types=1);

namespace App\Loan;

use InvalidArgumentException;

/**
 * Аннуитетный расчёт — та же формула, что во фронтенде (src/entities/loan/model/credit.ts).
 * Сервер считает сам и не доверяет числам от клиента.
 */
final class LoanCalculator
{
    /** Платёж не должен превышать эту долю дохода */
    public const MAX_PAYMENT_TO_INCOME_RATIO = 0.5;

    /** P = S * i * (1+i)^n / ((1+i)^n - 1), i = годовая ставка / 12 */
    public static function annuityPayment(float $amount, float $annualRate, int $termMonths): float
    {
        if ($amount <= 0 || $termMonths <= 0) {
            throw new InvalidArgumentException('Сумма и срок должны быть положительными');
        }
        if ($annualRate < 0) {
            throw new InvalidArgumentException('Ставка не может быть отрицательной');
        }

        $i = $annualRate / 12;
        if ($i == 0.0) {
            return self::round2($amount / $termMonths);
        }

        $factor = (1 + $i) ** $termMonths;

        return self::round2($amount * $i * $factor / ($factor - 1));
    }

    public static function calculate(LoanType $type, int $amount, int $termMonths): LoanCalculation
    {
        $monthlyPayment = self::annuityPayment($amount, $type->product()->annualRate, $termMonths);
        $totalRepaid = self::round2($monthlyPayment * $termMonths);

        return new LoanCalculation(
            monthlyPayment: $monthlyPayment,
            paymentsCount: $termMonths,
            totalRepaid: $totalRepaid,
            totalInterest: self::round2($totalRepaid - $amount),
            requiredIncome: (int) (ceil($monthlyPayment / self::MAX_PAYMENT_TO_INCOME_RATIO / 100) * 100),
        );
    }

    /**
     * Округление до копеек как Math.round(v * 100) / 100 в JS,
     * чтобы сервер и браузер давали одинаковые числа (PHP round() на границах .005 ведёт себя иначе).
     */
    public static function round2(float $value): float
    {
        return floor($value * 100 + 0.5) / 100;
    }
}
