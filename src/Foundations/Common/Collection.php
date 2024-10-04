<?php

namespace Ds\Foundations\Common;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;
use SeekableIterator;

class Collection extends \ArrayIterator implements SeekableIterator, \Traversable, \Iterator, ArrayAccess, \Serializable, \Countable, JsonSerializable, Arrayable
{
    public function validate()
    {}
    protected $array = null;
    protected $position = 0;
    public function __construct(array $array)
    {
        $this->array = $array;
        $this->position = 0; // Initialize position
    }

    // Return the current element
    public function current(): mixed
    {
        $this->validate();
        return $this->array[$this->position];
    }

    // Return the current key
    public function key(): int
    {
        $this->validate();
        return $this->position;
    }

    // Move forward to the next element
    public function next(): void
    {
        ++$this->position;
    }

    // Rewind the iterator to the first element
    public function rewind(): void
    {
        $this->validate();
        $this->position = 0;
    }

    // Checks if the current position is valid
    public function valid(): bool
    {
        $this->validate();
        return isset($this->array[$this->position]);
    }

    public function offsetSet($offset, $value): void
    {
        $this->validate();
        if (is_null($offset)) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        $this->validate();
        return isset($this->array[$offset]);
    }

    public function offsetUnset($offset): void
    {
        $this->validate();
        unset($this->array[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $this->validate();
        return isset($this->array[$offset]) ? $this->array[$offset] : null;
    }

    // implements all
    public function add($key, $value)
    {
        $this->validate();
        return Arr::add($this->array, $key, $value);
    }
    public function collapse()
    {
        $this->validate();
        return Arr::collapse($this->array);
    }
    public function crossJoin(...$arrays)
    {
        $this->validate();
        return Arr::crossJoin($this->array, ...$arrays);
    }
    public function divide()
    {
        $this->validate();
        return Arr::divide($this->array);
    }
    public function dot($prepend = '')
    {
        $this->validate();
        return Arr::dot($this->array, $prepend = '');
    }
    public function undot()
    {
        $this->validate();
        return Arr::undot($this->array);
    }
    public function except($keys)
    {
        $this->validate();
        return Arr::except($this->array, $keys);
    }
    public function exists($key)
    {
        $this->validate();
        return Arr::exists($this->array, $key);
    }
    public function first( ? callable $callback = null, $default = null)
    {
        $this->validate();
        return Arr::first($this->array, $callback, $default);
    }
    public function last( ? callable $callback = null, $default = null)
    {
        $this->validate();
        return Arr::last($this->array, $callback, $default);
    }
    public function take($limit)
    {
        $this->validate();
        return Arr::take($this->array, $limit);
    }
    public function flatten($depth = INF)
    {
        $this->validate();
        return Arr::flatten($this->array, $depth);
    }
    public function forget($keys)
    {
        $this->validate();
        return Arr::forget($this->array, $keys);
    }
    public function get($key, $default = null)
    {
        $this->validate();
        return Arr::get($this->array, $key, $default);
    }
    public function has($keys)
    {
        $this->validate();
        return Arr::has($this->array, $keys);
    }
    public function hasAny($keys)
    {
        $this->validate();
        return Arr::hasAny($this->array, $keys);
    }
    public function isAssoc($array)
    {
        $this->validate();
        return Arr::isAssoc($this->array);
    }
    public function isList($array)
    {
        $this->validate();
        return Arr::isList($this->array);
    }
    public function keyBy($keyBy)
    {
        $this->validate();
        return Arr::keyBy($this->array, $keyBy);
    }
    public function prependKeysWith($prependWith)
    {
        $this->validate();
        return Arr::prependKeysWith($this->array, $prependWith);
    }
    public function only($keys)
    {
        $this->validate();
        return Arr::only($this->array, $keys);
    }
    public function pluck($value, $key = null)
    {
        $this->validate();
        return Arr::pluck($this->array, $value, $key);
    }
    public function map(callable $callback)
    {
        $this->validate();
        return Arr::map($this->array, $callback);
    }
    public function mapWithKeys(callable $callback)
    {
        $this->validate();
        return Arr::mapWithKeys($this->array, $callback);
    }
    public function mapSpread(callable $callback)
    {
        $this->validate();
        return Arr::mapSpread($this->array, $callback);
    }
    public function prepend($value, $key = null)
    {
        $this->validate();
        return Arr::prepend($this->array, $value, $key);
    }
    public function pull($key, $default = null)
    {
        $this->validate();
        return Arr::pull($this->array, $key, $default);
    }
    public function random($number = null, $preserveKeys = false)
    {
        $this->validate();
        return Arr::random($this->array, $number, $preserveKeys);
    }
    public function set($key, $value)
    {
        $this->validate();
        return Arr::set($this->array, $key, $value);
    }
    public function shuffle($array)
    {
        $this->validate();
        return Arr::shuffle($this->array);
    }
    public function sort($callback = null)
    {
        $this->validate();
        return Arr::sort($this->array, $callback);
    }
    public function sortDesc($callback = null)
    {
        $this->validate();
        return Arr::sortDesc($this->array, $callback);
    }
    public function sortRecursive($options = SORT_REGULAR, $descending = false)
    {
        $this->validate();
        return Arr::sortRecursive($this->array, $options, $descending);
    }
    public function sortRecursiveDesc($options = SORT_REGULAR)
    {
        $this->validate();
        return Arr::sortRecursiveDesc($this->array, $options);
    }
    public function toCssClasses($array)
    {
        $this->validate();
        return Arr::toCssClasses($this->array);
    }
    public function toCssStyles($array)
    {
        $this->validate();
        return Arr::toCssStyles($this->array);
    }
    public function whereNotNull()
    {
        $this->validate();
        return Arr::whereNotNull($this->array);
    }
    public function wrap($value)
    {
        $this->validate();
        return Arr::wrap($value);
    }
    public function __toString() : string
    {
        $this->validate();
        return json_encode($this->array);
    }
    public function toArray()
    {
        $this->validate();
        return $this->array;
    }
    public function toJson($options = 0)
    {
        $this->validate();
        return json_encode($this->array, $options);
    }
    public function __sleep()
    {
        $this->validate();
    }
    public function serialize() : string
    {
        $this->validate();
        return serialize($this->array);
    }
    public function __serialize(): array
    {
        $this->validate();
        return $this->array;
    }
    public function jsonSerialize(): mixed
    {
        $this->validate();
        return $this->array;
    }
    public function __get($name)
    {
        $this->validate();
        return $this->{$name};
    }
    public function __debugInfo(): array
    {
        $this->validate();
        return $this->array;
    }
    public function dump()
    {
        $this->validate();
        dd($this->array);
    }
}
