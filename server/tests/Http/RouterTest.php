<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = (new Router())
            ->get('/api/health', static fn (): Response => Response::json(['ok' => true]))
            ->post('/admin/{id}/status', static fn (Request $r, array $p): Response => Response::text('id=' . $p['id']));
    }

    public function testMatchesStaticRoute(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/api/health'));

        self::assertSame(200, $response->status);
        self::assertSame('{"ok":true}', $response->body);
    }

    public function testHeadIsHandledAsGet(): void
    {
        self::assertSame(200, $this->router->dispatch(new Request('HEAD', '/api/health'))->status);
    }

    public function testPassesPathParameters(): void
    {
        self::assertSame('id=42', $this->router->dispatch(new Request('POST', '/admin/42/status'))->body);
    }

    public function testParameterDoesNotCrossSlash(): void
    {
        self::assertSame(404, $this->router->dispatch(new Request('POST', '/admin/4/2/status'))->status);
    }

    public function testUnknownApiPathIsJson404(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/api/nope'));

        self::assertSame(404, $response->status);
        self::assertSame('{"error":"not_found"}', $response->body);
    }

    public function testWrongMethodIs405WithAllowHeader(): void
    {
        $response = $this->router->dispatch(new Request('POST', '/api/health'));

        self::assertSame(405, $response->status);
        self::assertSame('GET', $response->headers['Allow']);
    }

    public function testNormalizesTrailingSlash(): void
    {
        self::assertSame('/admin', Request::normalizePath('/admin/'));
        self::assertSame('/', Request::normalizePath(''));
    }
}
