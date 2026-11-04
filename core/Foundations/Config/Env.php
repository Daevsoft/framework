<?php

declare(strict_types=1);

namespace Ds\Foundations\Config;

/** v3 namespace adapter for the v4 environment helper. */
class Env
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return \env($key, $default);
    }
}
