<?php

declare(strict_types=1);

namespace Tests\Routing;

use Ds\Container\Container;
use Ds\Http\Request;
use Ds\Http\RequestDispatcher;
use Ds\Http\Response;
use Ds\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RequestDispatcherTest extends TestCase
{
    public function testDispatchesStaticRouteAndConvertsStringResponse(): void
    {
        $router = new Router();
        $router->get('/health', static fn (): string => 'ok');

        $response = (new RequestDispatcher($router))->dispatch(
            new Request([], [], 'GET', '/health')
        );

        self::assertSame(200, $response->status());
        self::assertSame('ok', $response->body());
    }

    public function testDispatchesRouteParametersToCallable(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn (int $id): Response => Response::json(['id' => $id]));

        $response = (new RequestDispatcher($router))->dispatch(
            new Request([], [], 'GET', '/users/42')
        );

        self::assertSame(200, $response->status());
        self::assertSame('{"id":42}', $response->body());
    }

    public function testDispatchesControllerWithRequestAndTypedRouteParameter(): void
    {
        $router = new Router();
        $router->get('/items/{id}', [DispatcherTestController::class, 'show']);

        $response = (new RequestDispatcher($router, new Container()))->dispatch(
            new Request([], [], 'GET', '/items/7')
        );

        self::assertSame(200, $response->status());
        self::assertSame('{"id":7,"method":"GET"}', $response->body());
    }

    public function testUnknownRouteReturnsNotFound(): void
    {
        $response = (new RequestDispatcher(new Router()))->dispatch(
            new Request([], [], 'GET', '/missing')
        );

        self::assertSame(404, $response->status());
    }
}

final class DispatcherTestController
{
    public function show(Request $request, int $id): Response
    {
        return Response::json(['id' => $id, 'method' => $request->getMethod()]);
    }
}
