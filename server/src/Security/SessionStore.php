<?php

declare(strict_types=1);

namespace App\Security;

interface SessionStore
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;
}
