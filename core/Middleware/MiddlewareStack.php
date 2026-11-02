<?php

declare(strict_types=1);

namespace Ds\Middleware;

use InvalidArgumentException;

final class MiddlewareStack
{
    /** @var list<callable|MiddlewareInterface> */
    protected array $stack = [];

    public function push(callable|MiddlewareInterface $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function prepend(callable|MiddlewareInterface $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function remove(callable|MiddlewareInterface $middleware): void
    {
        $this->stack = array_values(array_filter(
            $this->stack,
            static fn (callable|MiddlewareInterface $registered): bool => $registered !== $middleware
        ));
    }

    public function clear(): void
    {
        $this->stack = [];
    }

    public function all(): array
    {
        return $this->stack;
    }

    public function count(): int
    {
        return count($this->stack);
    }
}
