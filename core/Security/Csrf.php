<?php

declare(strict_types=1);

namespace Ds\Security;

use Ds\Context\Context;
use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        $session = Context::default()->get('session');
        if ($session === null) {
            throw new RuntimeException('CSRF tokens require SessionMiddleware.');
        }

        $secret = (string) env('APP_KEY', '');
        if (strlen($secret) < 32) {
            throw new RuntimeException('APP_KEY must contain at least 32 characters to sign CSRF tokens.');
        }

        return hash_hmac('sha256', $session->id(), $secret);
    }

    public static function verify(mixed $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        try {
            return hash_equals(self::token(), $token);
        } catch (RuntimeException) {
            return false;
        }
    }
}
