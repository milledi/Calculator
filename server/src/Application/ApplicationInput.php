<?php

declare(strict_types=1);

namespace App\Application;

use App\Loan\LoanType;

/** Проверенные данные заявки */
final readonly class ApplicationInput
{
    public function __construct(
        public LoanType $loanType,
        public int $amount,
        public int $termMonths,
        public string $fullName,
        public float $monthlyIncome,
    ) {
    }
}
