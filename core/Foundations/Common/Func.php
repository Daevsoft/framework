<?php

declare(strict_types=1);

namespace Ds\Foundations\Common;

/** Small v3 debug/path helpers implemented without process-global state. */
final class Func
{
    public static bool $isDebug = false;

    public static function check(mixed $value, mixed $force = false): void
    {
        if (self::$isDebug || $force) {
            echo '<pre>' . htmlspecialchars(print_r($value, true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }
    }

    public static function isSlash(): bool
    {
        return DIRECTORY_SEPARATOR === '/';
    }

    public static function replaceSlash(string $text): string
    {
        return self::isSlash() ? str_replace('\\', '/', $text) : $text;
    }
}
