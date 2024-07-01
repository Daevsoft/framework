<?php

namespace Ds\Foundations\Common;

class Func
{
    public static bool $isDebug = false;
    public static function check($value, $force = false)
    {
        if (self::$isDebug || $force) {
            echo '<pre>';
            echo print_r($value, true);
            echo '</pre>';
        }
    }
    
    public static function isSlash()
    {
        return strpos(__DIR__, '/') >= 0;
    }

    public static function replaceSlash($text)
    {
        if (self::isSlash()) {
            return str_ireplace('\\', '/', $text);
        } else {
            return $text;
        }
    }

}
