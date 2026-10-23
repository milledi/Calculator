<?php

declare(strict_types=1);

namespace App\Application;

use App\Loan\LoanCalculation;

/**
 * Хранилище заявок. Строка заявки — массив со столбцами таблицы applications:
 * id, loan_type, amount, term_months, monthly_income, full_name,
 * monthly_payment, total_repaid, total_interest, status, created_at.
 */
interface ApplicationRepository
{
    /** @return array<string, mixed> созданная строка */
    public function create(ApplicationInput $input, LoanCalculation $calculation): array;

    /** @return list<array<string, mixed>> новые сверху */
    public function list(?ApplicationStatus $status = null): array;

    /** @return array<string, int> количество заявок по коду статуса */
    public function countByStatus(): array;

    /** @return bool false, если заявки с таким id нет */
    public function updateStatus(int $id, ApplicationStatus $status): bool;
}
