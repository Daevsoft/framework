<?php

declare(strict_types=1);

namespace Ds\Pool;

use Closure;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * A bounded LIFO resource pool.
 *
 * The pool owns resources after release. Configure a disposer when pooled
 * resources need explicit cleanup (for example, closing a connection).
 */
final class Pool
{
    protected array $items = [];
    protected array $pooledIds = [];
    private array $reservedIds = [];
    private bool $clearing = false;
    private int $clearVersion = 0;
    protected ?Closure $validator;
    protected ?Closure $disposer;

    public function __construct(
        protected int $maxSize = 100,
        ?callable $validator = null,
        ?callable $disposer = null
    ) {
        if ($maxSize < 1) {
            throw new InvalidArgumentException('Pool maxSize must be greater than zero.');
        }

        $this->validator = $validator === null ? null : Closure::fromCallable($validator);
        $this->disposer = $disposer === null ? null : Closure::fromCallable($disposer);
    }

    public function acquire(): mixed
    {
        if ($this->clearing) {
            throw new LogicException('Cannot acquire resources while the pool is being cleared.');
        }

        while ($this->items !== []) {
            $item = array_pop($this->items);
            $id = $this->identity($item);
            if ($id !== null) {
                unset($this->pooledIds[$id]);
                $this->reservedIds[$id] = true;
            }

            try {
                if ($this->isValid($item)) {
                    return $item;
                }

                $this->dispose($item);
            } finally {
                if ($id !== null) {
                    unset($this->reservedIds[$id]);
                }
            }
        }

        return null;
    }

    public function release(mixed $item): void
    {
        if ($this->clearing) {
            throw new LogicException('Cannot release resources while the pool is being cleared.');
        }

        if ($item === null) {
            return;
        }

        $id = $this->identity($item);
        if ($id !== null && (isset($this->pooledIds[$id]) || isset($this->reservedIds[$id]))) {
            throw new LogicException('The same resource cannot be released into a pool more than once.');
        }

        $version = $this->clearVersion;
        if ($id !== null) {
            // Reserve before invoking user validators, which may yield in a coroutine.
            $this->reservedIds[$id] = true;
        }

        try {
            if (!$this->isValid($item)) {
                $this->dispose($item);
                return;
            }

            // A clear may have started while a validator was suspended.
            if ($this->clearing || $version !== $this->clearVersion) {
                throw new LogicException('Cannot complete a resource release after pool clearing has started.');
            }

            if (count($this->items) >= $this->maxSize) {
                $this->dispose($item);
                return;
            }

            $this->items[] = $item;
            if ($id !== null) {
                $this->pooledIds[$id] = true;
            }
        } finally {
            if ($id !== null) {
                unset($this->reservedIds[$id]);
            }
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

    public function size(): int
    {
        return count($this->items);
    }

    public function maxSize(): int
    {
        return $this->maxSize;
    }

    public function clear(): void
    {
        if ($this->clearing) {
            return;
        }

        $this->clearing = true;
        ++$this->clearVersion;
        $items = $this->items;
        $this->items = [];
        $this->pooledIds = [];

        $failure = null;
        try {
            foreach ($items as $item) {
                try {
                    $this->dispose($item);
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

    private function isValid(mixed $item): bool
    {
        if ($this->validator === null) {
            return true;
        }

        try {
            return ($this->validator)($item) === true;
        } catch (Throwable $exception) {
            $this->dispose($item);
            throw $exception;
        }
    }

    private function dispose(mixed $item): void
    {
        if ($this->disposer !== null) {
            ($this->disposer)($item);
        }
    }

    private function identity(mixed $item): ?string
    {
        if (is_object($item)) {
            return 'object:' . spl_object_id($item);
        }
        if (is_resource($item)) {
            return 'resource:' . get_resource_id($item);
        }

        return null;
    }
}
