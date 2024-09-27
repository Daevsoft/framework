<?php

namespace Ds\Foundations\Config;

use Ds\Dir;

class Env
{
    public static function get($key, $default = NULL)
    {
        global $CACHE_CONFIG;
        if ($CACHE_CONFIG == null) {
            require_once Dir::$CONFIG_TEMP;
        }
        return $CACHE_CONFIG[$key] ?? $default;
    }
}
