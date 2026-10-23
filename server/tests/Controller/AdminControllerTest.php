<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Application\ApplicationInput;
use App\Controller\AdminController;
use App\Http\Request;
use App\Loan\LoanCalculator;
use App\Loan\LoanType;
use App\Security\BasicAuth;
use App\Security\Csrf;
use App\Tests\Support\ArraySession;
use App\Tests\Support\InMemoryApplicationRepository;
use App\View\View;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class AdminControllerTest extends TestCase
{
    private InMemoryApplicationRepository $repository;
    private ArraySession $session;
    private AdminController $controller;

    protected function setUp(): void
    {
        $this->repository = new InMemoryApplicationRepository();
        $this->session = new ArraySession();
        $repository = $this->repository;

        $this->controller = new AdminController(
            static fn () => $repository,
            new BasicAuth('admin', 's3cret'),
            new Csrf($this->session),
            new View(dirname(__DIR__, 2) . '/templates', new DateTimeZone('Europe/Moscow')),
        );

        $this->addApplication('Иванов Иван');
    }

    private function addApplication(string $fullName): void
    {
        $this->repository->create(
            new ApplicationInput(LoanType::Auto, 1_000_000, 60, $fullName, 100_000),
            LoanCalculator::calculate(LoanType::Auto, 1_000_000, 60),
        );
    }

    /** @param array<string, string> $query */
    private static function get(array $query = [], ?string $user = 'admin', ?string $password = 's3cret'): Request
    {
        return new Request('GET', '/admin', query: $query, authUser: $user, authPassword: $password);
    }

    /** @param array<string, string> $form */
    private static function post(array $form, ?string $user = 'admin', ?string $password = 's3cret'): Request
    {
        return new Request('POST', '/admin/1/status', form: $form, authUser: $user, authPassword: $password);
    }

    public function testRequiresBasicAuth(): void
    {
        $response = $this->controller->index(self::get(user: null, password: null));

        self::assertSame(401, $response->status);
        self::assertStringStartsWith('Basic realm="admin"', $response->headers['WWW-Authenticate']);
    }

    public function testRejectsWrongPassword(): void
    {
        self::assertSame(401, $this->controller->index(self::get(password: 'wrong'))->status);
    }

    public function testListsApplications(): void
    {
        $response = $this->controller->index(self::get());

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Иванов Иван', $response->body);
        self::assertStringContainsString('Автокредит', $response->body);
        self::assertStringContainsString('1 000 000 ₽', str_replace("\u{00A0}", ' ', $response->body));
        self::assertStringContainsString("frame-ancestors 'none'", $response->headers['Content-Security-Policy']);
    }

    public function testEscapesUserInput(): void
    {
        // Валидатор такое не пропустит, но в базе может оказаться что угодно — вывод всё равно экранируется
        $this->addApplication('<script>alert(1)</script>');

        $body = $this->controller->index(self::get())->body;

        self::assertStringNotContainsString('<script>alert(1)</script>', $body);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
    }

    public function testFiltersByStatus(): void
    {
        $body = $this->controller->index(self::get(['status' => 'approved']))->body;

        self::assertStringContainsString('В этом статусе заявок нет', $body);
    }

    public function testUpdatesStatusAndRedirectsBackToFilter(): void
    {
        $token = (new Csrf($this->session))->token();

        $response = $this->controller->updateStatus(
            self::post(['_csrf' => $token, 'status' => 'approved', 'filter' => 'new']),
            '1',
        );

        self::assertSame(303, $response->status);
        self::assertSame('/admin?status=new&updated=1', $response->headers['Location']);
        self::assertSame('approved', $this->repository->rows[1]['status']);
    }

    public function testRejectsMissingCsrfToken(): void
    {
        (new Csrf($this->session))->token();

        $response = $this->controller->updateStatus(self::post(['status' => 'approved']), '1');

        self::assertSame(403, $response->status);
        self::assertSame('new', $this->repository->rows[1]['status']);
    }

    public function testRejectsUnknownStatusAndBadId(): void
    {
        $token = (new Csrf($this->session))->token();

        self::assertSame(400, $this->controller->updateStatus(self::post(['_csrf' => $token, 'status' => 'deleted']), '1')->status);
        self::assertSame(400, $this->controller->updateStatus(self::post(['_csrf' => $token, 'status' => 'approved']), 'abc')->status);
        self::assertSame(404, $this->controller->updateStatus(self::post(['_csrf' => $token, 'status' => 'approved']), '999')->status);
    }

    public function testUpdateRequiresAuth(): void
    {
        $token = (new Csrf($this->session))->token();

        $response = $this->controller->updateStatus(self::post(['_csrf' => $token, 'status' => 'approved'], password: 'x'), '1');

        self::assertSame(401, $response->status);
    }
}
