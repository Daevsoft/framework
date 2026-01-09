<?php

declare(strict_types=1);

namespace Ds\Support;

class Utils
{
    public static function value(mixed $value): mixed
    {
        return is_callable($value) ? $value() : $value;
    }
}


