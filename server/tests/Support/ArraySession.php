<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Security\SessionStore;

final class ArraySession implements SessionStore
{
    /** @var array<string, mixed> */
    public array $data = [];

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
