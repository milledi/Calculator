<?php

declare(strict_types=1);

namespace App\Application;

use App\Loan\LoanType;

/**
 * Серверная проверка заявки. Правила повторяют zod-схему фронтенда
 * (src/entities/applicant/model/schema.ts): фронтенду мы не доверяем.
 */
final class ApplicationValidator
{
    /** Буквы, пробелы, апостроф и дефис, 2–120 символов */
    private const FULL_NAME_PATTERN = "/^[А-Яа-яЁёA-Za-z\\s\\p{Zs}'-]{2,120}$/u";

    /** Верхняя граница дохода: столбец NUMERIC(14,2) */
    private const MAX_INCOME = 999_999_999_999.99;

    public function validate(mixed $data): ValidationResult
    {
        if (!is_array($data) || (array_is_list($data) && $data !== [])) {
            return ValidationResult::invalid([self::issue('', 'Ожидался объект с данными заявки')]);
        }

        $issues = [];

        $loanType = is_string($data['loanType'] ?? null) ? LoanType::tryFrom($data['loanType']) : null;
        if ($loanType === null) {
            $issues[] = self::issue('loanType', 'Выберите тип кредита: mortgage, auto, consumer или payday');
        }

        $amount = self::toInt($data['amount'] ?? null);
        if ($amount === null) {
            $issues[] = self::issue('amount', 'Сумма должна быть целым числом');
        } elseif ($loanType !== null && !$loanType->product()->acceptsAmount($amount)) {
            $p = $loanType->product();
            $issues[] = self::issue('amount', "Сумма для «{$p->title}»: от {$p->minAmount} до {$p->maxAmount} ₽");
        }

        $termMonths = self::toInt($data['termMonths'] ?? null);
        if ($termMonths === null) {
            $issues[] = self::issue('termMonths', 'Срок должен быть целым числом месяцев');
        } elseif ($termMonths <= 0) {
            $issues[] = self::issue('termMonths', 'Срок должен быть положительным');
        } elseif ($loanType !== null && !$loanType->product()->acceptsTerm($termMonths)) {
            $p = $loanType->product();
            $issues[] = self::issue('termMonths', "Срок для «{$p->title}»: от {$p->minTermMonths} до {$p->maxTermMonths} мес");
        }

        $fullName = null;
        $fullNameIssue = null;
        if (!is_string($data['fullName'] ?? null)) {
            $fullNameIssue = 'Укажите ФИО';
        } else {
            $fullName = self::trim($data['fullName']);
            $length = mb_strlen($fullName);
            $fullNameIssue = match (true) {
                $length < 2 => 'Укажите ФИО (минимум 2 символа)',
                $length > 120 => 'ФИО слишком длинное',
                preg_match(self::FULL_NAME_PATTERN, $fullName) !== 1 => 'ФИО может содержать только буквы, пробелы и дефисы',
                default => null,
            };
        }
        if ($fullNameIssue !== null) {
            $issues[] = self::issue('fullName', $fullNameIssue);
        }

        $income = $data['monthlyIncome'] ?? null;
        if ((!is_int($income) && !is_float($income)) || !is_finite((float) $income)) {
            $issues[] = self::issue('monthlyIncome', 'Доход должен быть числом');
        } elseif ($income <= 0) {
            $issues[] = self::issue('monthlyIncome', 'Доход должен быть положительным');
        } elseif ($income > self::MAX_INCOME) {
            $issues[] = self::issue('monthlyIncome', 'Доход указан некорректно');
        }

        if (($data['hasCitizenship'] ?? null) !== true) {
            $issues[] = self::issue('hasCitizenship', 'Требуется гражданство РФ');
        }
        if (($data['consentGiven'] ?? null) !== true) {
            $issues[] = self::issue('consentGiven', 'Требуется согласие на обработку персональных данных');
        }

        if ($issues !== []) {
            return ValidationResult::invalid($issues);
        }

        return ValidationResult::valid(new ApplicationInput(
            loanType: $loanType,
            amount: $amount,
            termMonths: $termMonths,
            fullName: $fullName,
            monthlyIncome: (float) $income,
        ));
    }

    /** Целое число из JSON: 5000000 и 5000000.0 подходят, 123.45 и "5000000" — нет */
    private static function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) && is_finite($value) && floor($value) === $value && abs($value) <= PHP_INT_MAX) {
            return (int) $value;
        }

        return null;
    }

    /** trim() с учётом юникодных пробелов, как String.prototype.trim в JS */
    private static function trim(string $value): string
    {
        return preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $value) ?? $value;
    }

    /** @return array{path: string, message: string} */
    private static function issue(string $path, string $message): array
    {
        return ['path' => $path, 'message' => $message];
    }
}
