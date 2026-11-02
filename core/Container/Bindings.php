<?php

declare(strict_types=1);

namespace Ds\Container;

final class Bindings
{
    protected array $bindings = [];

    public function set(
        string $abstract,
        mixed $concrete,
        bool $shared = false,
        string $scope = 'transient'
    ): void {
        $this->bindings[$abstract] = compact('concrete', 'shared', 'scope');
    }

    public function get(string $abstract): ?array
    {
        return $this->bindings[$abstract] ?? null;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }
}
