<?php

declare(strict_types=1);

namespace Ds\Pool;

class Pool
{
    protected array $items = [];

    public function acquire(): mixed
    {
        return array_pop($this->items);
    }

    public function release(mixed $item): void
    {
        $this->items[] = $item;
    }
}


