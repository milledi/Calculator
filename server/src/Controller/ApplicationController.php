<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\ApplicationRepository;
use App\Application\ApplicationValidator;
use App\Http\Request;
use App\Http\Response;
use App\Loan\LoanCalculator;
use JsonException;

final class ApplicationController
{
    /** @param \Closure(): ApplicationRepository $repository подключение к БД открываем только когда нужно */
    public function __construct(
        private readonly \Closure $repository,
        private readonly ApplicationValidator $validator = new ApplicationValidator(),
    ) {
    }

    /** POST /api/applications */
    public function create(Request $request): Response
    {
        if ($request->bodyTooLarge) {
            return Response::json(['error' => 'payload_too_large'], 413);
        }
        if (!$request->isJson()) {
            return Response::json(['error' => 'unsupported_media_type', 'issues' => [
                ['path' => '', 'message' => 'Ожидается Content-Type: application/json'],
            ]], 415);
        }

        try {
            $data = $request->json();
        } catch (JsonException) {
            return Response::json(['error' => 'invalid_json', 'issues' => [
                ['path' => '', 'message' => 'Тело запроса — некорректный JSON'],
            ]], 400);
        }

        $result = $this->validator->validate($data);
        if (!$result->isValid()) {
            return Response::json(['error' => 'validation_error', 'issues' => $result->issues], 400);
        }

        $input = $result->input;
        $calculation = LoanCalculator::calculate($input->loanType, $input->amount, $input->termMonths);
        $row = ($this->repository)()->create($input, $calculation);

        return Response::json(self::present($row), 201);
    }

    /**
     * Строка БД -> JSON с нормальными типами (PDO отдаёт NUMERIC строками).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function present(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'loan_type' => (string) $row['loan_type'],
            'amount' => (float) $row['amount'],
            'term_months' => (int) $row['term_months'],
            'monthly_income' => (float) $row['monthly_income'],
            'full_name' => (string) $row['full_name'],
            'monthly_payment' => (float) $row['monthly_payment'],
            'total_repaid' => (float) $row['total_repaid'],
            'total_interest' => (float) $row['total_interest'],
            'status' => (string) $row['status'],
            'created_at' => (string) $row['created_at'],
        ];
    }
}
