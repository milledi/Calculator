<?php

declare(strict_types=1);

namespace App\Security;

/**
 * CSRF-токен для форм админки. Basic-авторизацию браузер подставляет сам,
 * поэтому без токена чужой сайт мог бы отправить форму смены статуса от имени админа.
 */
final class Csrf
{
    private const KEY = 'csrf_token';

    public function __construct(private readonly SessionStore $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function isValid(?string $submitted): bool
    {
        $token = $this->session->get(self::KEY);

        return is_string($token) && $token !== '' && is_string($submitted) && hash_equals($token, $submitted);
    }
}
