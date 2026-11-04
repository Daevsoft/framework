<?php

declare(strict_types=1);

namespace Ds\Foundations\Session;

class Session
{
    public static function user(): mixed
    {
        $user = \session('user');
        if (!is_string($user) || !self::looksSerialized($user)) {
            return $user;
        }

        // Decode legacy scalar/array session payloads without allowing classes.
        $decoded = @unserialize($user, ['allowed_classes' => false]);
        if ($decoded === false && $user !== 'b:0;') {
            return $user;
        }

        return is_object($decoded) ? $user : $decoded;
    }

    private static function looksSerialized(string $value): bool
    {
        $value = trim($value);
        if ($value === 'N;') {
            return true;
        }
        return strlen($value) >= 4
            && $value[1] === ':'
            && in_array($value[0], ['s', 'a', 'b', 'i', 'd', 'N'], true)
            && in_array(substr($value, -1), [';', '}'], true);
    }

    public static function destroy(): void
    {
        \session_manager()->destroy();
    }
}
