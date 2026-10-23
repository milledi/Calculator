<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ApplicationController;
use App\Http\Request;
use App\Tests\Support\InMemoryApplicationRepository;
use PHPUnit\Framework\TestCase;

/** HTTP-контракт POST /api/applications — тот же, что был у Express-версии */
final class ApplicationControllerTest extends TestCase
{
    private InMemoryApplicationRepository $repository;
    private ApplicationController $controller;

    protected function setUp(): void
    {
        $this->repository = new InMemoryApplicationRepository();
        $repository = $this->repository;
        $this->controller = new ApplicationController(static fn () => $repository);
    }

    private static function post(string $body, string $contentType = 'application/json'): Request
    {
        return new Request('POST', '/api/applications', headers: ['content-type' => $contentType], body: $body);
    }

    public function testCreatesApplicationWithServerSideCalculation(): void
    {
        $response = $this->controller->create(self::post(json_encode([
            'loanType' => 'mortgage',
            'amount' => 5_000_000,
            'termMonths' => 120,
            'fullName' => '  Иванов Иван Иванович ',
            'monthlyIncome' => 200_000,
            'hasCitizenship' => true,
            'consentGiven' => true,
            // клиентские числа сервер игнорирует
            'monthlyPayment' => 1,
        ])));

        self::assertSame(201, $response->status);
        $body = json_decode($response->body, true);
        self::assertSame(1, $body['id']);
        self::assertSame('Иванов Иван Иванович', $body['full_name']);
        self::assertSame(90_092.6, $body['monthly_payment']);
        self::assertSame(10_811_112.0, $body['total_repaid']);
        self::assertSame(5_811_112.0, $body['total_interest']);
        self::assertSame('new', $body['status']);
        self::assertCount(1, $this->repository->rows);
    }

    public function testValidationErrorFormat(): void
    {
        $response = $this->controller->create(self::post(json_encode([
            'loanType' => 'mortgage',
            'amount' => 5_000_000,
            'termMonths' => 1,
            'fullName' => 'Иванов Иван',
            'monthlyIncome' => 200_000,
            'hasCitizenship' => true,
            'consentGiven' => true,
        ])));

        self::assertSame(400, $response->status);
        self::assertSame([
            'error' => 'validation_error',
            'issues' => [['path' => 'termMonths', 'message' => 'Срок для «Ипотека»: от 12 до 360 мес']],
        ], json_decode($response->body, true));
        self::assertSame([], $this->repository->rows);
    }

    public function testInvalidJson(): void
    {
        $response = $this->controller->create(self::post('{"loanType":'));

        self::assertSame(400, $response->status);
        self::assertSame('invalid_json', json_decode($response->body, true)['error']);
    }

    public function testRequiresJsonContentType(): void
    {
        self::assertSame(415, $this->controller->create(self::post('loanType=mortgage', 'application/x-www-form-urlencoded'))->status);
    }

    public function testRejectsTooLargeBody(): void
    {
        $request = new Request('POST', '/api/applications', headers: ['content-type' => 'application/json'], bodyTooLarge: true);

        self::assertSame(413, $this->controller->create($request)->status);
    }
}
