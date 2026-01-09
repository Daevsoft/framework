<?php

declare(strict_types=1);

namespace Ds\Debug;

class Inspector
{
    public function snapshot(): array
    {
        return [
            'memory' => memory_get_usage(true),
            'time' => microtime(true),
        ];
    }
}


