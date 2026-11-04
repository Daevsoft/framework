<?php

declare(strict_types=1);

namespace Ds\Helper;

/** Dependency-free compatibility helpers for the v3 Ds\Helper\Str API. */
final class Str
{
    public static function contains(string $text, string $find): bool
    {
        return $find === '' || stripos($text, $find) !== false;
    }

    public static function replace(string $source, string|array $findText, string $replaceText = ''): string
    {
        if (is_string($findText)) {
            return str_ireplace($findText, $replaceText, $source);
        }

        foreach ($findText as $find => $replacement) {
            $source = self::replace($source, (string) $find, (string) $replacement);
        }

        return $source;
    }

    public static function empty(mixed $value): bool
    {
        return $value === '';
    }

    public static function nullEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
