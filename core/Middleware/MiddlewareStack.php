<?php

declare(strict_types=1);

namespace Ds\Middleware;

final class MiddlewareStack
{
    /** @var list<callable> */
    protected array $stack = [];

    public function push(callable $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function prepend(callable $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function remove(callable $middleware): void
    {
        $this->stack = array_values(array_filter(
            $this->stack,
            static fn (callable $registered): bool => $registered !== $middleware
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
