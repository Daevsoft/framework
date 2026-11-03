<?php

declare(strict_types=1);

namespace Ds\Pool;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class PoolManager
{
    protected array $pools = [];
    private bool $clearing = false;
    private array $forgetting = [];

    public function __construct(protected int $maxPools = 64)
    {
        if ($maxPools < 1) {
            throw new InvalidArgumentException('maxPools must be greater than zero.');
        }
    }

    public function get(
        string $name,
        int $maxSize = 100,
        ?callable $validator = null,
        ?callable $disposer = null
    ): Pool {
        if ($this->clearing) {
            throw new RuntimeException('Cannot create or retrieve pools while PoolManager is being cleared.');
        }

        if (isset($this->forgetting[$name])) {
            throw new RuntimeException("Pool [{$name}] is being forgotten and cannot be recreated yet.");
        }

        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if (count($this->pools) >= $this->maxPools) {
            throw new RuntimeException('PoolManager capacity exceeded.');
        }

        return $this->pools[$name] = new Pool($maxSize, $validator, $disposer);
    }

    public function forget(string $name): void
    {
        if ($this->clearing) {
            throw new RuntimeException('Cannot forget pools while PoolManager is being cleared.');
        }

        if (!isset($this->pools[$name])) {
            return;
        }

        if (isset($this->forgetting[$name])) {
            return;
        }

        $pool = $this->pools[$name];
        $this->forgetting[$name] = true;
        unset($this->pools[$name]);

        try {
            $pool->clear();
        } finally {
            unset($this->forgetting[$name]);
        }
    }

    public function clear(): void
    {
        if ($this->clearing) {
            return;
        }

        $this->clearing = true;
        $pools = $this->pools;
        $this->pools = [];
        $failure = null;

        try {
            foreach ($pools as $pool) {
                try {
                    $pool->clear();
                } catch (Throwable $exception) {
                    $failure ??= $exception;
                }
            }
        } finally {
            $this->clearing = false;
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    public function __destruct()
    {
        try {
            $this->clear();
        } catch (Throwable) {
            // Destructors must not throw during worker shutdown.
        }
    }

    public function count(): int
    {
        return count($this->pools);
    }
}
