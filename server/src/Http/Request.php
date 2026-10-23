<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Request
{
    /** Максимальный размер тела запроса (как лимит express.json() по умолчанию) */
    public const MAX_BODY_BYTES = 100 * 1024;

    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $form    поля формы (application/x-www-form-urlencoded)
     * @param array<string, string> $headers имена в нижнем регистре
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $form = [],
        public array $headers = [],
        public string $body = '',
        public ?string $authUser = null,
        public ?string $authPassword = null,
        /** Тело больше MAX_BODY_BYTES — обрезано и не читается */
        public bool $bodyTooLarge = false,
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $key => $name) {
            if (isset($_SERVER[$key])) {
                $headers[$name] = (string) $_SERVER[$key];
            }
        }

        $body = (string) file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);

        [$user, $password] = self::basicCredentials($_SERVER, $headers['authorization'] ?? null);

        return new self(
            method: strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            path: self::normalizePath((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH)),
            query: $_GET,
            form: $_POST,
            headers: $headers,
            body: substr($body, 0, self::MAX_BODY_BYTES),
            authUser: $user,
            authPassword: $password,
            bodyTooLarge: strlen($body) > self::MAX_BODY_BYTES,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isJson(): bool
    {
        return str_contains(strtolower($this->header('content-type') ?? ''), 'application/json');
    }

    /** @throws \JsonException */
    public function json(): mixed
    {
        return json_decode($this->body, true, 32, JSON_THROW_ON_ERROR);
    }

    public function query(string $key): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function input(string $key): ?string
    {
        $value = $this->form[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** "/admin/" -> "/admin", пустой путь -> "/" */
    public static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : $path;
    }

    /**
     * Логин и пароль Basic-авторизации. mod_php кладёт их в PHP_AUTH_USER/PW,
     * в остальных случаях разбираем заголовок Authorization сами.
     *
     * @param array<string, mixed> $server
     * @return array{0: ?string, 1: ?string}
     */
    private static function basicCredentials(array $server, ?string $authorization): array
    {
        if (isset($server['PHP_AUTH_USER'])) {
            return [(string) $server['PHP_AUTH_USER'], (string) ($server['PHP_AUTH_PW'] ?? '')];
        }
        if ($authorization !== null && stripos($authorization, 'basic ') === 0) {
            $decoded = base64_decode(substr($authorization, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $password] = explode(':', $decoded, 2);

                return [$user, $password];
            }
        }

        return [null, null];
    }
}
