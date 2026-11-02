<?php

declare(strict_types=1);

namespace Ds\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Small framework-native collection with no third-party dependency.
 *
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<array-key, mixed>
 */
final class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable
{
    public function __construct(private array $items = [])
    {
    }

    public static function make(iterable $items = []): self
    {
        return new self(is_array($items) ? $items : iterator_to_array($items));
    }

    public function all(): array { return $this->items; }
    public function toArray(): array { return $this->items; }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return $this->items === []; }
    public function isNotEmpty(): bool { return $this->items !== []; }

    public function get(string|int $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function put(string|int $key, mixed $value): self
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function forget(string|int $key): self
    {
        unset($this->items[$key]);
        return $this;
    }

    public function push(mixed ...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items === [] ? $default : reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items === [] ? $default : end($this->items);
        }

        foreach (array_reverse($this->items, true) as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        foreach ($this->items as $key => $value) {
            $mapped[$key] = $callback($value, $key);
        }
        return new self($mapped);
    }

    public function filter(?callable $callback = null): self
    {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function values(): self { return new self(array_values($this->items)); }
    public function keys(): self { return new self(array_keys($this->items)); }

    public function pluck(string|int $key): self
    {
        $values = [];
        foreach ($this->items as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $values[] = $item[$key];
            } elseif (is_object($item) && isset($item->{$key})) {
                $values[] = $item->{$key};
            }
        }
        return new self($values);
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function merge(iterable $items): self
    {
        $incoming = is_array($items) ? $items : iterator_to_array($items);
        return new self(array_merge($this->items, $incoming));
    }

    public function groupBy(string|callable $groupBy): self
    {
        $groups = [];
        foreach ($this->items as $key => $value) {
            $group = is_callable($groupBy)
                ? $groupBy($value, $key)
                : (is_array($value) ? ($value[$groupBy] ?? null) : (is_object($value) ? ($value->{$groupBy} ?? null) : null));
            $groups[$group ?? ''] ??= [];
            $groups[$group ?? ''][] = $value;
        }

        foreach ($groups as $key => $values) {
            $groups[$key] = new self($values);
        }

        return new self($groups);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool { return isset($this->items[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }
    public function offsetUnset(mixed $offset): void { unset($this->items[$offset]); }
}
