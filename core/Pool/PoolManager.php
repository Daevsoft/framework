<?php

declare(strict_types=1);

namespace Ds\Pool;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class PoolManager
{
    protected array $pools = [];

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
        if (!isset($this->pools[$name])) {
            return;
        }

        $pool = $this->pools[$name];
        unset($this->pools[$name]);
        $pool->clear();
    }

    public function clear(): void
    {
        $pools = $this->pools;
        $this->pools = [];
        $failure = null;

        foreach ($pools as $pool) {
            try {
                $pool->clear();
            } catch (Throwable $exception) {
                $failure ??= $exception;
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    public function count(): int
    {
        return count($this->pools);
    }
}
