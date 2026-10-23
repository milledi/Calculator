<?php

declare(strict_types=1);

namespace App\Security;

/** Обычная PHP-сессия; стартует при первом обращении, cookie только для /admin */
final class NativeSession implements SessionStore
{
    public function get(string $key): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('admin_sid');
        session_start([
            'cookie_path' => '/admin',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}
