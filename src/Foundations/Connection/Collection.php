<?php
namespace Ds\Foundations\Connection;

use Ds\Foundations\Connection\Models\DsModel;

class DbCollection extends DsModel implements \Iterator

{
    private $array = [];
    private $position = 0;
    public function __construct(array $array)
    {
        $this->array = $array;
        $this->position = 0; // Initialize position
    }

    // Return the current element
    public function current(): mixed
    {
        return $this->array[$this->position];
    }

    // Return the current key
    public function key(): int
    {
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
        $this->position = 0;
    }

    // Checks if the current position is valid
    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }
}
class Collection extends \IteratorIterator

{
    public function __construct()
    {
        parent::__construct(new \ArrayIterator());
    }
}
