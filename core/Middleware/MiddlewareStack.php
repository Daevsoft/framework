<?php

declare(strict_types=1);

namespace Ds\Middleware;

class MiddlewareStack
{
    protected array $stack = [];

    public function push(callable $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function all(): array
    {
        return $this->stack;
    }
}


