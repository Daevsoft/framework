<?php

declare(strict_types=1);

namespace Ds\Foundations\Security;

/** Compatibility facade for the v3 CSRF helper. */
final class Csrf
{
    public static function token(): string
    {
        return \Ds\Security\Csrf::token();
    }

    public static function generate(): string
    {
        return self::token();
    }

    public static function verify(mixed $token): bool
    {
        return \Ds\Security\Csrf::verify($token);
    }
}
