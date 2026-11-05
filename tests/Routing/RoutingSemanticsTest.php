<?php

declare(strict_types=1);

namespace Tests\Routing;

use Ds\Http\Request;
use Ds\Http\RequestDispatcher;
use Ds\Http\Response;
use Ds\Routing\Router;
use App\\Support\\Route as RouteDsl;\nuse App\\Support\\RouteRegistry;\nuse InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RoutingSemanticsTest extends TestCase
{
    public function testDuplicateRouteDefinitionsFailFast(): void
    {
        $router = new Router();
        $router->add('GET', '/items', static fn () => 'first');

        $this->expectException(InvalidArgumentException::class);
        $router->add('GET', '/items/', static fn () => 'second');
    }

    public function testMalformedAndDuplicateParametersAreRejected(): void
    {
        $router = new Router();

        try {
            $router->add('GET', '/items/{id}/{id}', static fn () => 'invalid');
            self::fail('Duplicate parameter names should be rejected.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $router->add('GET', '/items/{bad-name}', static fn () => 'invalid');
    }

    public function testConstraintsAreCompiledAndAppliedToDynamicRoutes(): void
    {
        $router = new Router();
        $router->add('GET', '/orders/{id}', static fn (int $id) => (string) $id)
            ->whereNumber('id');

        self::assertSame('42', $router->match('GET', '/orders/42')?->params['id']);
        self::assertNull($router->match('GET', '/orders/not-a-number'));
    }

    public function testUuidConstraintSupportsQuantifiers(): void
    {
        $router = new Router();
        $router->add('GET', '/records/{id}', static fn () => 'ok')->whereUuid('id');

        self::assertNotNull($router->match('GET', '/records/550e8400-e29b-41d4-a716-446655440000'));
        self::assertNull($router->match('GET', '/records/not-a-uuid'));
    }

    public function testEncodedSlashCannotChangeRouteSegmentBoundaries(): void
    {
        $router = new Router();
        $router->add('GET', '/files/{name}', static fn () => 'ok');

        self::assertNull($router->match('GET', '/files/folder%2Fsecret.txt'));
    }

    public function testHeadFallsBackToGetAndDispatcherSuppressesBody(): void
    {
        $router = new Router();
        $router->add('GET', '/health', static fn () => Response::text('healthy'));

        $route = $router->match('HEAD', '/health');
        self::assertNotNull($route);
        self::assertSame('HEAD', $route->method);

        $response = (new RequestDispatcher($router))->dispatch(new Request([], [], 'HEAD', '/health'));
        self::assertSame(200, $response->status());
        self::assertSame('', $response->body());
    }

    public function testDispatcherDistinguishesMethodNotAllowedFromNotFound(): void
    {
        $router = new Router();
        $router->add('POST', '/items', static fn () => Response::text('created'));
        $dispatcher = new RequestDispatcher($router);

        $methodNotAllowed = $dispatcher->dispatch(new Request([], [], 'GET', '/items'));
        self::assertSame(405, $methodNotAllowed->status());
        self::assertSame('OPTIONS, POST', $methodNotAllowed->headers()['Allow']);

        $notFound = $dispatcher->dispatch(new Request([], [], 'GET', '/missing'));
        self::assertSame(404, $notFound->status());
    }

    public function testAutomaticOptionsReturnsAllowHeader(): void
    {
        $router = new Router();
        $router->add('GET', '/items', static fn () => 'list');
        $router->add('POST', '/items', static fn () => 'create');

        $response = (new RequestDispatcher($router))->dispatch(new Request([], [], 'OPTIONS', '/items'));

        self::assertSame(204, $response->status());
        self::assertSame('GET, HEAD, OPTIONS, POST', $response->headers()['Allow']);
    }


    public function testNamedRouteGeneratesEncodedRelativeAndAbsoluteUrls(): void
    {
        $router = new Router();
        $router->add('GET', '/users/{id}', static fn () => 'show')
            ->whereNumber('id')
            ->name('users.show');

        self::assertSame('/users/42?tab=profile', $router->urlFor('users.show', ['id' => 42, 'tab' => 'profile']));
        self::assertSame('https://example.test/users/42', $router->urlFor('users.show', ['id' => 42], true, 'https://example.test'));
    }

    public function testNamedRouteRejectsMissingAndConstraintViolatingParameters(): void
    {
        $router = new Router();
        $router->add('GET', '/users/{id}', static fn () => 'show')
            ->whereNumber('id')
            ->name('users.show');

        try {
            $router->urlFor('users.show');
            self::fail('Missing route parameters must fail.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $router->urlFor('users.show', ['id' => 'not-numeric']);
    }

    public function testDuplicateRouteNamesAreRejected(): void
    {
        $router = new Router();
        $router->add('GET', '/users', static fn () => 'index')->name('users.index');

        $this->expectException(InvalidArgumentException::class);
        $router->add('GET', '/users/{id}', static fn () => 'show')->name('users.index');
    }


    public function testFallbackRunsOnlyWhenNoRegisteredPathMatches(): void
    {
        $router = new Router();
        $router->add('GET', '/known', static fn () => 'known');
        $router->setFallback(static fn (Request $request) => Response::text('fallback:' . $request->getPath()));
        $dispatcher = new RequestDispatcher($router);

        self::assertSame('known', $dispatcher->dispatch(new Request([], [], 'GET', '/known'))->body());
        self::assertSame('fallback:/missing', $dispatcher->dispatch(new Request([], [], 'GET', '/missing'))->body());
        self::assertSame(405, $dispatcher->dispatch(new Request([], [], 'POST', '/known'))->status());
    }

    public function testApiResourceRegistersOnlyApiActionsAndSupportsOnlySelector(): void
    {
        $router = new Router();
        RouteRegistry::setRouter($router);

        RouteDsl::apiResource('users', 'App\\Http\\Controllers\\UserController')
            ->only(['index', 'show']);

        $routes = $router->all();
        self::assertCount(2, $routes);
        self::assertSame(['users.index', 'users.show'], array_map(
            static fn ($route) => $route->routeName,
            $routes
        ));
        self::assertNotNull($router->match('GET', '/users'));
        self::assertNotNull($router->match('GET', '/users/42'));
        self::assertNull($router->match('POST', '/users'));
    }

    public function testResourceExceptSelectorRemovesUnselectedActions(): void
    {
        $router = new Router();
        RouteRegistry::setRouter($router);

        RouteDsl::resource('posts', 'App\\Http\\Controllers\\PostController')
            ->except(['create', 'edit']);

        self::assertCount(5, $router->all());
        self::assertNull($router->match('GET', '/posts/create'));
        self::assertNotNull($router->match('POST', '/posts'));
    }

    public function testFallbackCanOnlyBeRegisteredOnce(): void
    {
        $router = new Router();
        $router->setFallback(static fn () => 'first');

        $this->expectException(InvalidArgumentException::class);
        $router->setFallback(static fn () => 'second');
    }


    public function testStaticRoutesTakePrecedenceOverDynamicRoutesRegardlessOfRegistrationOrder(): void
    {
        $router = new Router();
        $router->add('GET', '/users/{id}', static fn () => 'dynamic');
        $router->add('GET', '/users/me', static fn () => 'static');

        self::assertSame('static', ($router->match('GET', '/users/me')?->handler)());
        self::assertSame('dynamic', ($router->match('GET', '/users/123')?->handler)());
    }

    public function testOverlappingDynamicRoutesUseDeterministicRegistrationOrder(): void
    {
        $router = new Router();
        $router->add('GET', '/items/{value}', static fn () => 'first');
        $router->add('GET', '/items/{slug}', static fn () => 'second');

        self::assertSame('first', ($router->match('GET', '/items/abc')?->handler)());
    }

    public function testConstraintMustReferenceAnExistingRouteParameter(): void
    {
        $route = (new Router())->add('GET', '/items/{id}', static fn () => 'ok');

        $this->expectException(InvalidArgumentException::class);
        $route->whereNumber('missing');
    }

    public function testNestedPrefixAndMiddlewareGroupsRestoreState(): void
    {
        $router = new Router();
        RouteRegistry::setRouter($router);

        RouteDsl::prefix('/api', static function (): void {
            RouteDsl::middleware('outer', static function (): void {
                RouteDsl::prefix('/v1', static function (): void {
                    RouteDsl::middleware('inner', static function (): void {
                        RouteDsl::get('/users', static fn () => 'ok')->middleware('route');
                    });
                });
            });
        });
        $route = $router->match('GET', '/api/v1/users');
        self::assertNotNull($route);
        self::assertSame(['outer', 'inner', 'route'], $route->middlewares);

        try {
            RouteDsl::prefix('/temporary', static function (): void {
                throw new \\RuntimeException('registration failed');
            });
        } catch (\\RuntimeException) {
            self::assertTrue(true);
        }
        RouteDsl::get('/outside', static fn () => 'ok');
        self::assertNotNull($router->match('GET', '/outside'));
    }

    public function testQueryStringDoesNotParticipateInRouteMatching(): void
    {
        $router = new Router();
        $router->add('GET', '/search', static fn () => 'ok');

        self::assertNotNull($router->match('GET', '/search?q=router'));
    }
}
