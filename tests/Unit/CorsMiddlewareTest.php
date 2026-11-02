<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\CorsMiddleware;
use Ds\Http\Request;
use Ds\Http\Response;
use PHPUnit\Framework\TestCase;

final class CorsMiddlewareTest extends TestCase
{
    public function testShortCircuitsPreflightRequests(): void
    {
        $called = false;
        $response = (new CorsMiddleware())->handle(
            new Request([], [], 'OPTIONS', '/users'),
            static function () use (&$called): Response {
                $called = true;
                return Response::text('should not run');
            }
        );

        self::assertSame(204, $response->status());
        self::assertFalse($called);
        self::assertSame('600', $response->headers()['Access-Control-Max-Age']);
    }

    public function testAddsCorsHeadersWithoutMutatingTheOriginalResponse(): void
    {
        $middleware = new CorsMiddleware();
        $original = Response::text('ok');

        $response = $middleware->handle(
            new Request(),
            static fn (): Response => $original
        );

        self::assertSame('ok', $response->body());
        self::assertArrayHasKey('Access-Control-Allow-Origin', $response->headers());
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $original->headers());
    }
}
