<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\ApplicationValidator;
use App\Loan\LoanType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Те же случаи, что во фронтенд-тестах схемы (src/entities/applicant/model/__tests__/schema.test.ts) */
final class ApplicationValidatorTest extends TestCase
{
    private const VALID = [
        'loanType' => 'mortgage',
        'amount' => 5_000_000,
        'termMonths' => 120,
        'fullName' => 'Иванов Иван Иванович',
        'monthlyIncome' => 200_000,
        'hasCitizenship' => true,
        'consentGiven' => true,
    ];

    private ApplicationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ApplicationValidator();
    }

    public function testAcceptsValidApplication(): void
    {
        $result = $this->validator->validate(self::VALID);

        self::assertTrue($result->isValid());
        self::assertSame([], $result->issues);
        self::assertSame(LoanType::Mortgage, $result->input->loanType);
        self::assertSame(5_000_000, $result->input->amount);
        self::assertSame(120, $result->input->termMonths);
        self::assertSame(200_000.0, $result->input->monthlyIncome);
    }

    public function testTrimsFullName(): void
    {
        $result = $this->validator->validate(['fullName' => "  Иванов Иван\u{00A0} "] + self::VALID);

        self::assertSame('Иванов Иван', $result->input->fullName);
    }

    public function testAcceptsWholeFloatAmount(): void
    {
        self::assertTrue($this->validator->validate(['amount' => 5_000_000.0] + self::VALID)->isValid());
    }

    public function testAcceptsHyphenAndApostrophe(): void
    {
        self::assertTrue($this->validator->validate(['fullName' => "Римский-Корсаков O'Neil"] + self::VALID)->isValid());
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidCases(): iterable
    {
        yield 'пустое ФИО' => [['fullName' => ''], 'fullName'];
        yield 'цифры в ФИО' => [['fullName' => 'Иван123'], 'fullName'];
        yield 'HTML-вставка в ФИО' => [['fullName' => '<script>alert(1)</script>'], 'fullName'];
        yield 'один символ' => [['fullName' => 'И'], 'fullName'];
        yield 'слишком длинное ФИО' => [['fullName' => str_repeat('а', 121)], 'fullName'];
        yield 'ФИО не строка' => [['fullName' => ['Иван']], 'fullName'];
        yield 'отрицательная сумма' => [['amount' => -100], 'amount'];
        yield 'нулевая сумма' => [['amount' => 0], 'amount'];
        yield 'сумма выше максимума' => [['amount' => 100_000_000], 'amount'];
        yield 'дробная сумма' => [['amount' => 123.45], 'amount'];
        yield 'сумма строкой' => [['amount' => '5000000'], 'amount'];
        yield 'неизвестный тип кредита' => [['loanType' => 'crypto'], 'loanType'];
        yield 'срок вне диапазона продукта' => [['termMonths' => 1], 'termMonths'];
        yield 'отрицательный срок' => [['termMonths' => -12], 'termMonths'];
        yield 'дробный срок' => [['termMonths' => 12.5], 'termMonths'];
        yield 'без гражданства' => [['hasCitizenship' => false], 'hasCitizenship'];
        yield 'гражданство строкой' => [['hasCitizenship' => 'true'], 'hasCitizenship'];
        yield 'без согласия 152-ФЗ' => [['consentGiven' => false], 'consentGiven'];
        yield 'нулевой доход' => [['monthlyIncome' => 0], 'monthlyIncome'];
        yield 'отрицательный доход' => [['monthlyIncome' => -1], 'monthlyIncome'];
        yield 'доход строкой' => [['monthlyIncome' => '100000'], 'monthlyIncome'];
        yield 'доход больше столбца БД' => [['monthlyIncome' => 1e15], 'monthlyIncome'];
    }

    /** @param array<string, mixed> $patch */
    #[DataProvider('invalidCases')]
    public function testRejectsInvalidField(array $patch, string $field): void
    {
        $result = $this->validator->validate($patch + self::VALID);

        self::assertFalse($result->isValid());
        self::assertContains($field, array_column($result->issues, 'path'));
    }

    public function testRangeMessageNamesProduct(): void
    {
        $result = $this->validator->validate(['termMonths' => 1] + self::VALID);

        self::assertSame(
            [['path' => 'termMonths', 'message' => 'Срок для «Ипотека»: от 12 до 360 мес']],
            $result->issues,
        );
    }

    public function testMissingFieldsAreReported(): void
    {
        $paths = array_column($this->validator->validate([])->issues, 'path');

        self::assertSame(
            ['loanType', 'amount', 'termMonths', 'fullName', 'monthlyIncome', 'hasCitizenship', 'consentGiven'],
            $paths,
        );
    }

    public function testRejectsNonObjectBody(): void
    {
        foreach (['строка', 42, null, [1, 2, 3]] as $body) {
            self::assertFalse($this->validator->validate($body)->isValid());
        }
    }
}
