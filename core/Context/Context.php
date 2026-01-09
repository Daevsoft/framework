<?php

declare(strict_types=1);

namespace Ds\Context;

/**
 * Penyimpanan context yang aman untuk coroutine.
 */
class Context
{
    protected array $store = [];

    public function set(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function clear(): void
    {
        $this->store = [];
    }
}


