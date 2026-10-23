<?php

declare(strict_types=1);

namespace App\Loan;

final readonly class LoanCalculation
{
    public function __construct(
        /** Ежемесячный аннуитетный платёж, ₽ */
        public float $monthlyPayment,
        public int $paymentsCount,
        /** Общая сумма выплат, ₽ */
        public float $totalRepaid,
        /** Переплата (проценты), ₽ */
        public float $totalInterest,
        /** Минимально необходимый доход, ₽ (округлён вверх до сотен) */
        public int $requiredIncome,
    ) {
    }
}
