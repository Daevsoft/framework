<?php

declare(strict_types=1);

namespace Ds\Container;

class Bindings
{
    protected array $bindings = [];

    public function set(string $abstract, mixed $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function get(string $abstract): ?array
    {
        return $this->bindings[$abstract] ?? null;
    }
}


