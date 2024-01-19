<?php

namespace Ds\Foundations\Config;

class Env
{
    public static function get($key, $default = NULL)
    {
        global $CACHE_CONFIG;
        return $CACHE_CONFIG[$key] ?? $default;
    }
}
