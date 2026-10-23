<?php

declare(strict_types=1);

namespace App;

/** Настройки из переменных окружения (docker compose / .env) */
final readonly class Config
{
    public function __construct(
        public string $dbHost,
        public int $dbPort,
        public string $dbName,
        public string $dbUser,
        public string $dbPassword,
        public string $adminUser,
        public string $adminPassword,
        public string $timezone,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            dbHost: self::env('PGHOST', 'localhost'),
            dbPort: (int) self::env('PGPORT', '5432'),
            dbName: self::env('PGDATABASE', 'calc'),
            dbUser: self::env('PGUSER', 'calc'),
            dbPassword: self::env('PGPASSWORD', 'calc'),
            adminUser: self::env('ADMIN_USER', 'admin'),
            adminPassword: self::env('ADMIN_PASSWORD', 'admin'),
            timezone: self::env('APP_TIMEZONE', 'Europe/Moscow'),
        );
    }

    private static function env(string $key, string $default): string
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }
}
