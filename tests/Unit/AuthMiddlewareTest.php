<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\AuthMiddleware;
use Ds\Http\Request;
use Ds\Http\Response;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    public function testUnconfiguredAuthenticationFailsClosed(): void
    {
        $called = false;
        $response = (new AuthMiddleware())->handle(
            new Request(),
            static function () use (&$called): Response {
                $called = true;
                return Response::text('allowed');
            }
        );

        self::assertSame(501, $response->status());
        self::assertFalse($called);
    }
}
