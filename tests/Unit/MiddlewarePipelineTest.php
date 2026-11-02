<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\MiddlewareInterface;
use Ds\Middleware\MiddlewarePipeline;
use Ds\Middleware\MiddlewareStack;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class MiddlewarePipelineTest extends TestCase
{
    public function testExecutesMiddlewareInRegistrationOrder(): void
    {
        $stack = new MiddlewareStack();
        $trace = [];

        $stack->push(static function (Request $request, callable $next) use (&$trace): Response {
            $trace[] = 'first:before';
            $response = $next($request);
            $trace[] = 'first:after';
            return $response;
        });

        $stack->push(static function (Request $request, callable $next) use (&$trace): Response {
            $trace[] = 'second:before';
            $response = $next($request);
            $trace[] = 'second:after';
            return $response;
        });

        $response = (new MiddlewarePipeline($stack))->handle(
            new Request([], [], 'GET', '/'),
            static function (): Response {
                return Response::text('ok');
            }
        );

        self::assertSame('ok', $response->body());
        self::assertSame([
            'first:before',
            'second:before',
            'second:after',
            'first:after',
        ], $trace);
    }

    public function testExecutesMiddlewareObjects(): void
    {
        $stack = new MiddlewareStack();
        $stack->push(new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request)->withHeader('X-Middleware', 'active');
            }
        });

        $response = (new MiddlewarePipeline($stack))->handle(
            new Request(),
            static fn (): Response => Response::text('ok')
        );

        self::assertSame('active', $response->headers()['X-Middleware']);
    }

    public function testRejectsInvalidMiddlewareReturnValue(): void
    {
        $pipeline = new MiddlewarePipeline(new MiddlewareStack());

        $this->expectException(UnexpectedValueException::class);
        $pipeline->handle(new Request(), static fn () => 'not a response');
    }

    public function testCanRemoveMiddlewareAndClearStack(): void
    {
        $stack = new MiddlewareStack();
        $middleware = static fn (Request $request, callable $next): Response => $next($request);

        $stack->push($middleware);
        self::assertSame(1, $stack->count());

        $stack->remove($middleware);
        self::assertSame(0, $stack->count());

        $stack->push($middleware);
        $stack->clear();
        self::assertSame([], $stack->all());
    }
}
