<?php

declare(strict_types=1);

namespace App\Application;

use App\Loan\LoanCalculation;
use PDO;

final class PdoApplicationRepository implements ApplicationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(ApplicationInput $input, LoanCalculation $calculation): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO applications
               (loan_type, amount, term_months, monthly_income, full_name,
                monthly_payment, total_repaid, total_interest)
             VALUES (:loan_type, :amount, :term_months, :monthly_income, :full_name,
                     :monthly_payment, :total_repaid, :total_interest)
             RETURNING *',
        );
        $stmt->execute([
            'loan_type' => $input->loanType->value,
            'amount' => $input->amount,
            'term_months' => $input->termMonths,
            'monthly_income' => self::money($input->monthlyIncome),
            'full_name' => $input->fullName,
            'monthly_payment' => self::money($calculation->monthlyPayment),
            'total_repaid' => self::money($calculation->totalRepaid),
            'total_interest' => self::money($calculation->totalInterest),
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function list(?ApplicationStatus $status = null): array
    {
        if ($status === null) {
            return $this->pdo
                ->query('SELECT * FROM applications ORDER BY created_at DESC, id DESC')
                ->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare('SELECT * FROM applications WHERE status = :status ORDER BY created_at DESC, id DESC');
        $stmt->execute(['status' => $status->value]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStatus(): array
    {
        $rows = $this->pdo
            ->query('SELECT status, COUNT(*) AS n FROM applications GROUP BY status')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_map('intval', $rows);
    }

    public function updateStatus(int $id, ApplicationStatus $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE applications SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status->value, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** Деньги передаём строкой, чтобы NUMERIC получил ровно два знака без артефактов float */
    private static function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
