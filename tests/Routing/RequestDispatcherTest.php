<?php

declare(strict_types=1);

namespace Tests\Routing;

use Ds\Container\Container;
use Ds\Context\Context;
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

    public function testControllerInstancesAreNotRetainedByReflectionCache(): void
    {
        RetentionTestController::$lastInstance = null;
        $router = new Router();
        $router->get('/retention', [RetentionTestController::class, 'show']);
        $dispatcher = new RequestDispatcher($router);

        $dispatcher->dispatch(new Request([], [], 'GET', '/retention'));
        gc_collect_cycles();

        self::assertNotNull(RetentionTestController::$lastInstance);
        self::assertNull(RetentionTestController::$lastInstance->get());
    }

    public function testRequestContextIsAvailableDuringDispatchAndRemovedAfterward(): void
    {
        $router = new Router();
        $router->get('/origin', static fn (): string => app_url());
        $dispatcher = new RequestDispatcher($router);
        $context = Context::default();
        $context->remove('request');

        $response = $dispatcher->dispatch(
            new Request([], ['host' => 'framework.example.test'], 'GET', '/origin', 'https')
        );

        self::assertSame('https://framework.example.test', $response->body());
        self::assertFalse($context->has('request'));
    }

    public function testNestedDispatchRestoresPreviousRequestContext(): void
    {
        $context = Context::default();
        $previous = new Request([], ['host' => 'previous.example.test'], 'GET', '/', 'https');
        $context->set('request', $previous);
        $router = new Router();
        $router->get('/current', static fn (): string => app_url());

        $response = (new RequestDispatcher($router))->dispatch(
            new Request([], ['host' => 'current.example.test'], 'GET', '/current')
        );

        self::assertSame('http://current.example.test', $response->body());
        self::assertSame($previous, $context->get('request'));
        $context->remove('request');
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

final class RetentionTestController
{
    public static ?\WeakReference $lastInstance = null;

    public function __construct()
    {
        self::$lastInstance = \WeakReference::create($this);
    }

    public function show(): string
    {
        return 'ok';
    }
}
