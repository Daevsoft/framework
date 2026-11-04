<?php

declare(strict_types=1);

namespace Ds\Foundations\Common;

/** Small v3 debug/path helpers implemented without process-global state. */
final class Func
{
    public static bool $isDebug = false;

    public static function check(mixed $value, bool $force = false): void
    {
        if (self::$isDebug || $force) {
            \dump($value);
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
