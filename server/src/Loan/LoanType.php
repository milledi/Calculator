<?php

declare(strict_types=1);

namespace App\Loan;

enum LoanType: string
{
    case Mortgage = 'mortgage';
    case Auto = 'auto';
    case Consumer = 'consumer';
    case Payday = 'payday';

    public function product(): LoanProduct
    {
        return match ($this) {
            self::Mortgage => new LoanProduct('Ипотека', 0.18, 300_000, 30_000_000, 12, 360),
            self::Auto => new LoanProduct('Автокредит', 0.235, 100_000, 5_000_000, 12, 84),
            self::Consumer => new LoanProduct('Потребительский', 0.222, 50_000, 5_000_000, 6, 84),
            self::Payday => new LoanProduct('Деньги до зарплаты', 0.5, 5_000, 100_000, 1, 12),
        };
    }

    /** Название продукта по коду из БД; для неизвестного кода — сам код */
    public static function titleOf(string $code): string
    {
        return self::tryFrom($code)?->product()->title ?? $code;
    }
}
