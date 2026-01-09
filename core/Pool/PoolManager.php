<?php

declare(strict_types=1);

namespace Ds\Pool;

class PoolManager
{
    protected array $pools = [];

    public function get(string $name): Pool
    {
        return $this->pools[$name] ??= new Pool();
    }
}


