<?php

declare(strict_types=1);

namespace App\Security;

use App\Http\Request;
use App\Http\Response;

final readonly class BasicAuth
{
    public function __construct(
        private string $user,
        private string $password,
        private string $realm = 'admin',
    ) {
    }

    public function isAuthorized(Request $request): bool
    {
        if ($request->authUser === null || $request->authPassword === null) {
            return false;
        }

        // hash_equals сравнивает за постоянное время — по времени ответа пароль не подобрать.
        // Сравниваем обе части всегда, без раннего выхода.
        $userOk = hash_equals($this->user, $request->authUser);
        $passwordOk = hash_equals($this->password, $request->authPassword);

        return $userOk && $passwordOk;
    }

    public function challenge(): Response
    {
        return Response::text('Требуется авторизация', 401)
            ->withHeader('WWW-Authenticate', sprintf('Basic realm="%s", charset="UTF-8"', $this->realm));
    }
}
