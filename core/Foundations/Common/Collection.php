<?php

declare(strict_types=1);

namespace Ds\Foundations\Common;

use ArrayIterator;
use JsonSerializable;
use RuntimeException;

/**
 * Compatibility collection for applications migrating from v3.
 *
 * Legacy methods intentionally return arrays where v3 returned arrays.
 * New code should prefer Ds\Support\Collection.
 */
class Collection extends ArrayIterator implements JsonSerializable
{
    public function __construct(array $array = [])
    {
        parent::__construct($array);
    }

    public function validate(): void
    {
        // Kept as a no-op for source compatibility with v3 subclasses.
    }

    /**
     * Replace the iterator contents while retaining the legacy ArrayIterator
     * inheritance contract. ArrayIterator does not provide exchangeArray()
     * (that method belongs to ArrayObject).
     *
     * Reinitializing the parent also resets its internal iterator position.
     */
    private function replaceItems(array $items): void
    {
        parent::__construct($items);
    }

    public function add(mixed $key, mixed $value): array
    {
        $items = $this->getArrayCopy();
        if (!array_key_exists($key, $items)) {
            $items[$key] = $value;
        }
        return $items;
    }

    public function collapse(): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $items) {
            if (is_array($items)) {
                $result = array_merge($result, $items);
            } elseif ($items instanceof self) {
                $result = array_merge($result, $items->toArray());
            } elseif ($items instanceof \Traversable) {
                $result = array_merge($result, iterator_to_array($items));
            }
        }
        return $result;
    }

    public function crossJoin(array ...$arrays): array
    {
        $results = [[]];
        foreach (array_merge([$this->getArrayCopy()], $arrays) as $values) {
            $append = [];
            foreach ($results as $result) {
                foreach ($values as $value) {
                    $append[] = [...$result, $value];
                }
            }
            $results = $append;
        }
        return $results;
    }

    public function divide(): array
    {
        return [array_keys($this->getArrayCopy()), array_values($this->getArrayCopy())];
    }

    public function dot(string $prepend = ''): array
    {
        $result = [];
        $flatten = static function (array $items, string $prefix = '') use (&$flatten, &$result): void {
            foreach ($items as $key => $value) {
                $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if (is_array($value)) {
                    $flatten($value, $fullKey);
                } else {
                    $result[$fullKey] = $value;
                }
            }
        };
        $flatten($this->getArrayCopy(), $prepend);
        return $result;
    }

    public function undot(): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            self::setNested($result, (string) $key, $value);
        }
        return $result;
    }

    public function except(array|string $keys): array
    {
        $items = $this->getArrayCopy();
        foreach ((array) $keys as $key) {
            unset($items[$key]);
        }
        return $items;
    }

    public function exists(mixed $key): bool
    {
        return array_key_exists($key, $this->getArrayCopy());
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        foreach ($this->getArrayCopy() as $key => $value) {
            if ($callback === null || $callback($value, $key)) {
                return $value;
            }
        }
        return is_callable($default) ? $default() : $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        foreach (array_reverse($this->getArrayCopy(), true) as $key => $value) {
            if ($callback === null || $callback($value, $key)) {
                return $value;
            }
        }
        return is_callable($default) ? $default() : $default;
    }

    public function take(int $limit): array
    {
        return $limit < 0
            ? array_slice($this->getArrayCopy(), $limit, null, true)
            : array_slice($this->getArrayCopy(), 0, $limit, true);
    }

    public function flatten(int|float $depth = INF): array
    {
        $result = [];
        $flatten = static function (iterable $items, int|float $remaining) use (&$flatten, &$result): void {
            foreach ($items as $value) {
                if ((is_array($value) || $value instanceof \Traversable) && $remaining > 0) {
                    $flatten($value, $remaining === INF ? INF : $remaining - 1);
                } else {
                    $result[] = $value;
                }
            }
        };
        $flatten($this->getArrayCopy(), $depth);
        return $result;
    }

    public function forget(array|string $keys): array
    {
        $items = $this->getArrayCopy();
        foreach ((array) $keys as $key) {
            self::forgetNested($items, (string) $key);
        }
        return $items;
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        $items = $this->getArrayCopy();
        if (array_key_exists($key, $items)) {
            return $items[$key];
        }
        foreach (explode('.', (string) $key) as $segment) {
            if (is_array($items) && array_key_exists($segment, $items)) {
                $items = $items[$segment];
            } else {
                return is_callable($default) ? $default() : $default;
            }
        }
        return $items;
    }

    public function has(array|string $keys): bool
    {
        foreach ((array) $keys as $key) {
            if (!$this->exists($key) && $this->get($key, new \stdClass()) instanceof \stdClass) {
                return false;
            }
        }
        return true;
    }

    public function hasAny(array|string $keys): bool
    {
        foreach ((array) $keys as $key) {
            if ($this->exists($key) || !$this->get($key, new \stdClass()) instanceof \stdClass) {
                return true;
            }
        }
        return false;
    }

    public function isAssoc(): bool
    {
        return !array_is_list($this->getArrayCopy());
    }

    public function isList(): bool
    {
        return array_is_list($this->getArrayCopy());
    }

    public function keyBy(string|callable $keyBy): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            $newKey = is_callable($keyBy)
                ? $keyBy($value, $key)
                : (is_array($value) ? ($value[$keyBy] ?? null) : (is_object($value) ? ($value->{$keyBy} ?? null) : null));
            $result[$newKey ?? $key] = $value;
        }
        return $result;
    }

    public function prependKeysWith(string $prependWith): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            $result[$prependWith . $key] = $value;
        }
        return $result;
    }

    public function only(array|string $keys): array
    {
        return array_intersect_key($this->getArrayCopy(), array_fill_keys((array) $keys, true));
    }

    public function pluck(string|int $value, string|int|null $key = null): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $item) {
            $itemValue = self::valueAt($item, $value);
            if ($itemValue === null) {
                continue;
            }
            $itemKey = $key === null ? null : self::valueAt($item, $key);
            if ($itemKey === null) {
                $result[] = $itemValue;
            } else {
                $result[$itemKey] = $itemValue;
            }
        }
        return $result;
    }

    public function map(callable $callback): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        return $result;
    }

    public function mapWithKeys(callable $callback): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            foreach ((array) $callback($value, $key) as $newKey => $newValue) {
                $result[$newKey] = $newValue;
            }
        }
        return $result;
    }

    public function mapSpread(callable $callback): array
    {
        $result = [];
        foreach ($this->getArrayCopy() as $key => $value) {
            $result[$key] = $callback(...array_values((array) $value));
        }
        return $result;
    }

    public function prepend(mixed $value, string|int|null $key = null): array
    {
        $items = $this->getArrayCopy();
        if ($key === null) {
            array_unshift($items, $value);
            return $items;
        }
        return [$key => $value] + $items;
    }

    public function pull(string|int $key, mixed $default = null): mixed
    {
        $items = $this->getArrayCopy();
        $value = array_key_exists($key, $items) ? $items[$key] : $default;
        self::forgetNested($items, (string) $key);
        $this->replaceItems($items);
        return $value;
    }

    public function random(?int $number = null, bool $preserveKeys = false): mixed
    {
        $items = $this->getArrayCopy();
        if ($number === null) {
            if ($items === []) {
                throw new RuntimeException('Cannot select a random item from an empty collection.');
            }
            return $items[array_rand($items)];
        }
        if ($number < 1 || $number > count($items)) {
            throw new RuntimeException('Requested random item count is outside the collection size.');
        }
        $keys = (array) array_rand($items, $number);
        $selected = [];
        foreach ($keys as $key) {
            $selected[$key] = $items[$key];
        }
        return $preserveKeys ? $selected : array_values($selected);
    }

    public function set(string|int $key, mixed $value): array
    {
        $items = $this->getArrayCopy();
        self::setNested($items, (string) $key, $value);
        $this->replaceItems($items);
        return $items;
    }

    public function shuffle(): array
    {
        $items = $this->getArrayCopy();
        shuffle($items);
        return $items;
    }

    public function sort(?callable $callback = null): array
    {
        $items = $this->getArrayCopy();
        $callback === null ? asort($items) : uasort($items, $callback);
        return $items;
    }

    public function sortDesc(?callable $callback = null): array
    {
        $items = $this->getArrayCopy();
        $callback === null
            ? arsort($items)
            : uasort($items, static fn ($a, $b): int => $callback($b, $a));
        return $items;
    }

    public function sortRecursive(int $options = SORT_REGULAR, bool $descending = false): array
    {
        $sort = static function (array $items) use (&$sort, $options, $descending): array {
            foreach ($items as &$value) {
                if (is_array($value)) {
                    $value = $sort($value);
                }
            }
            unset($value);
            $descending ? krsort($items, $options) : ksort($items, $options);
            if (array_is_list($items)) {
                $descending ? rsort($items, $options) : sort($items, $options);
            }
            return $items;
        };
        return $sort($this->getArrayCopy());
    }

    public function sortRecursiveDesc(int $options = SORT_REGULAR): array
    {
        return $this->sortRecursive($options, true);
    }

    public function toCssClasses(): string
    {
        $classes = [];
        foreach ($this->getArrayCopy() as $class => $condition) {
            if (is_int($class)) {
                if (is_string($condition) && $condition !== '') {
                    $classes[] = $condition;
                }
            } elseif ($condition) {
                $classes[] = $class;
            }
        }
        return implode(' ', $classes);
    }

    public function toCssStyles(): string
    {
        $styles = [];
        foreach ($this->getArrayCopy() as $property => $value) {
            if (is_int($property)) {
                if (is_string($value) && str_contains($value, ':')) {
                    $styles[] = trim($value, " ;\t\n\r");
                }
            } elseif ($value !== null && $value !== false) {
                $styles[] = $property . ': ' . (is_bool($value) ? ($value ? '1' : '0') : $value);
            }
        }
        return $styles === [] ? '' : implode('; ', $styles) . ';';
    }

    public function whereNotNull(): array
    {
        return array_filter($this->getArrayCopy(), static fn ($value): bool => $value !== null);
    }

    public function wrap(mixed $value): array
    {
        return is_array($value) ? $value : [$value];
    }

    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    public function toJson(int $options = 0): string|false
    {
        return json_encode($this->getArrayCopy(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson() ?: '';
    }

    public function serialize(): string
    {
        return serialize($this->getArrayCopy());
    }

    public function unserialize(string $data): void
    {
        $items = unserialize($data, ['allowed_classes' => false]);
        $this->replaceItems(is_array($items) ? $items : []);
    }

    public function __serialize(): array
    {
        return $this->getArrayCopy();
    }

    public function __unserialize(array $data): void
    {
        $this->__construct($data);
    }

    public function __get(string $name): mixed
    {
        return $this->offsetExists($name) ? $this->offsetGet($name) : null;
    }

    public function __debugInfo(): array
    {
        return $this->getArrayCopy();
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    public function dump(): void
    {
        dump($this->getArrayCopy());
    }

    private static function valueAt(mixed $item, string|int $key): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }
        if (is_object($item)) {
            return $item->{$key} ?? null;
        }
        return null;
    }

    private static function setNested(array &$items, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$items;
        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
        $target = $value;
    }

    private static function forgetNested(array &$items, string $key): void
    {
        $segments = explode('.', $key);
        $target = &$items;
        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                return;
            }
            $target = &$target[$segment];
        }
        unset($target[$segments[0]]);
    }
}
