<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Минимальный роутер: метод + шаблон пути с параметрами вида {id}.
 * Обработчик получает Request и массив параметров пути.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable(Request, array<string, string>): Response}> */
    private array $routes = [];

    /** @param callable(Request, array<string, string>): Response $handler */
    public function get(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function post(string $pattern, callable $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function add(string $method, string $pattern, callable $handler): self
    {
        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            static fn (array $m): string => "(?P<{$m[1]}>[^/]+)",
            str_replace('.', '\.', $pattern),
        );
        $this->routes[] = ['method' => $method, 'regex' => "#^{$regex}$#", 'handler' => $handler];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        // HEAD обрабатываем как GET — тело Apache отбросит сам
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return ($route['handler'])($request, $params);
        }

        $isApi = str_starts_with($request->path, '/api/');
        if ($allowed !== []) {
            $response = $isApi
                ? Response::json(['error' => 'method_not_allowed'], 405)
                : Response::text('Метод не поддерживается', 405);

            return $response->withHeader('Allow', implode(', ', array_unique($allowed)));
        }

        return $isApi
            ? Response::json(['error' => 'not_found'], 404)
            : Response::text('Страница не найдена', 404);
    }
}
