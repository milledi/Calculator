<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Application\ApplicationInput;
use App\Application\ApplicationRepository;
use App\Application\ApplicationStatus;
use App\Loan\LoanCalculation;

/** Хранилище в памяти: строки в том же виде, что отдаёт PDO (числа NUMERIC — строками) */
final class InMemoryApplicationRepository implements ApplicationRepository
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function create(ApplicationInput $input, LoanCalculation $calculation): array
    {
        $id = count($this->rows) + 1;

        return $this->rows[$id] = [
            'id' => $id,
            'loan_type' => $input->loanType->value,
            'amount' => number_format($input->amount, 2, '.', ''),
            'term_months' => $input->termMonths,
            'monthly_income' => number_format($input->monthlyIncome, 2, '.', ''),
            'full_name' => $input->fullName,
            'monthly_payment' => number_format($calculation->monthlyPayment, 2, '.', ''),
            'total_repaid' => number_format($calculation->totalRepaid, 2, '.', ''),
            'total_interest' => number_format($calculation->totalInterest, 2, '.', ''),
            'status' => ApplicationStatus::New->value,
            'created_at' => '2026-10-23 12:00:00+00',
        ];
    }

    public function list(?ApplicationStatus $status = null): array
    {
        $rows = array_values(array_filter(
            $this->rows,
            static fn (array $r): bool => $status === null || $r['status'] === $status->value,
        ));

        return array_reverse($rows);
    }

    public function countByStatus(): array
    {
        return array_count_values(array_column($this->rows, 'status'));
    }

    public function updateStatus(int $id, ApplicationStatus $status): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        $this->rows[$id]['status'] = $status->value;

        return true;
    }
}
