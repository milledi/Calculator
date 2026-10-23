<?php

declare(strict_types=1);

namespace App\Loan;

/** Параметры кредитного продукта. Должны совпадать с src/shared/config/loanProducts.ts на фронтенде. */
final readonly class LoanProduct
{
    public function __construct(
        public string $title,
        /** Годовая ставка, доли единицы (0.18 = 18%) */
        public float $annualRate,
        public int $minAmount,
        public int $maxAmount,
        public int $minTermMonths,
        public int $maxTermMonths,
    ) {
    }

    public function acceptsAmount(int $amount): bool
    {
        return $amount >= $this->minAmount && $amount <= $this->maxAmount;
    }

    public function acceptsTerm(int $termMonths): bool
    {
        return $termMonths >= $this->minTermMonths && $termMonths <= $this->maxTermMonths;
    }
}
