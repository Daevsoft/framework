<?php

declare(strict_types=1);

namespace Ds\Debug;

class Profiler
{
    protected array $marks = [];

    public function mark(string $label): void
    {
        $this->marks[$label] = microtime(true);
    }

    public function measure(string $start, string $end): float
    {
        if (! isset($this->marks[$start], $this->marks[$end])) {
            return 0.0;
        }

        return $this->marks[$end] - $this->marks[$start];
    }
}


