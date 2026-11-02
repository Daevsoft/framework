<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\CorsMiddleware;
use Ds\Http\Request;
use Ds\Http\Response;
use PHPUnit\Framework\TestCase;

final class CorsMiddlewareTest extends TestCase
{
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
