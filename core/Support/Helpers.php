<?php

declare(strict_types=1);

namespace Ds\Support;

class Helpers
{
    public static function tap(mixed $value, callable $callback): mixed
    {
        $callback($value);
        return $value;
    }
}


