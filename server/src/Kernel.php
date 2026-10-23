<?php

declare(strict_types=1);

namespace App;

use App\Application\ApplicationRepository;
use App\Application\PdoApplicationRepository;
use App\Controller\AdminController;
use App\Controller\ApplicationController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Security\BasicAuth;
use App\Security\Csrf;
use App\Security\NativeSession;
use App\Security\SessionStore;
use App\View\View;
use DateTimeZone;
use Throwable;

/** Собирает зависимости и маршруты, превращает Request в Response */
final class Kernel
{
    private Router $router;

    /** @param \Closure(): ApplicationRepository $repository */
    public function __construct(
        Config $config,
        \Closure $repository,
        SessionStore $session,
        string $templatesDir,
    ) {
        $applications = new ApplicationController($repository);
        $admin = new AdminController(
            $repository,
            new BasicAuth($config->adminUser, $config->adminPassword),
            new Csrf($session),
            new View($templatesDir, new DateTimeZone($config->timezone)),
        );

        $this->router = (new Router())
            ->get('/api/health', static fn (): Response => Response::json(['ok' => true]))
            ->post('/api/applications', static fn (Request $r): Response => $applications->create($r))
            ->get('/admin', static fn (Request $r): Response => $admin->index($r))
            ->post('/admin/{id}/status', static fn (Request $r, array $p): Response => $admin->updateStatus($r, $p['id']));
    }

    public static function fromEnvironment(): self
    {
        $config = Config::fromEnvironment();

        // Подключаемся к БД лениво и один раз: /api/health работает и без базы
        $repository = null;
        $lazyRepository = static function () use ($config, &$repository): ApplicationRepository {
            return $repository ??= new PdoApplicationRepository(Database::connect($config));
        };

        return new self($config, $lazyRepository, new NativeSession(), dirname(__DIR__) . '/templates');
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (Throwable $e) {
            error_log(sprintf('[%s] %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

            return str_starts_with($request->path, '/api/')
                ? Response::json(['error' => 'internal_error'], 500)
                : Response::text('Внутренняя ошибка сервера', 500);
        }
    }
}
