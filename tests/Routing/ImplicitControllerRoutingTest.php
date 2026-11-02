<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Support\Route as RouteDsl;
use App\Support\RouteRegistry;
use Ds\Http\Request;
use Ds\Http\RequestDispatcher;
use Ds\Http\Response;
use Ds\Foundations\Routing\Attributes\Get as LegacyGet;
use Ds\Middleware\MiddlewareInterface;
use Ds\Middleware\MiddlewareRegistry;
use Ds\Foundations\Network\Middleware as LegacyMiddleware;
use Ds\Foundations\Network\Request as LegacyRequest;
use Ds\Foundations\Network\Response as LegacyResponse;
use Ds\Routing\Attributes\Get;
use Ds\Routing\Attributes\Post;
use Ds\Routing\Router;
use PHPUnit\Framework\TestCase;

final class ImplicitControllerRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        RouteRegistry::setRouter(new Router());
    }

    public function testRegistersControllerAttributesInsidePrefixGroup(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $route = RouteRegistry::getRouter()?->match('GET', '/members/all');
        self::assertNotNull($route);
        self::assertSame([ImplicitMemberController::class, 'index'], $route->handler);
    }

    public function testDispatchesImplicitControllerRouteParameters(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/detail/42')
        );

        self::assertSame(200, $response->status());
        self::assertSame('{"id":42}', $response->body());
    }

    protected function tearDown(): void
    {
        MiddlewareRegistry::clear();
    }

    public function testExecutesRouteMiddlewareAliases(): void
    {
        MiddlewareRegistry::alias('test-header', ImplicitHeaderMiddleware::class);
        RouteDsl::get('/protected', static fn (): Response => Response::text('ok'))
            ->middleware('test-header');

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/protected')
        );

        self::assertSame('ok', $response->body());
        self::assertSame('yes', $response->headers()['X-Test-Middleware']);
    }

    public function testLegacyMiddlewareCanShortCircuitWithNull(): void
    {
        MiddlewareRegistry::alias('legacy-deny', LegacyDenyMiddleware::class);
        RouteDsl::get('/legacy-denied', static fn (): Response => Response::text('allowed'))
            ->middleware('legacy-deny');

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/legacy-denied')
        );

        self::assertSame(403, $response->status());
    }

    public function testLegacyV3AttributeNamespaceRemainsCompatible(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);
        $route = RouteRegistry::getRouter()?->match('GET', '/members/legacy');

        self::assertNotNull($route);
        self::assertSame([ImplicitMemberController::class, 'legacy'], $route->handler);
    }

    public function testDispatchesLegacyV3RequestTypeThroughClassAlias(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/legacy-request')
        );

        self::assertSame(200, $response->status());
        self::assertSame('legacy-request:GET', $response->body());
    }

    public function testControllerMethodsCanReceiveRouteArgumentsWithoutRequest(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/slug/developer')
        );

        self::assertSame('slug:developer', $response->body());
    }

    public function testBindsRouteParametersByNameRatherThanDeclarationOrder(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/reordered/first/second')
        );

        self::assertSame('second:first', $response->body());
    }

    public function testInjectsRequestAfterRouteParameters(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/request-after/73')
        );

        self::assertSame('73:GET', $response->body());
    }

    public function testNamedRouteBindingIsReservedBeforePositionalFallback(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $response = (new RequestDispatcher(RouteRegistry::getRouter()))->dispatch(
            new Request([], [], 'GET', '/members/mixed/73/developer')
        );

        self::assertSame('developer:73', $response->body());
    }

    public function testRegistersMultipleHttpMethodsAndAttributeMiddleware(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $route = RouteRegistry::getRouter()?->match('POST', '/members');
        self::assertNotNull($route);
        self::assertSame(['auth'], $route->middlewares);
    }
}

final class LegacyDenyMiddleware extends LegacyMiddleware
{
    public function handle(LegacyRequest $request, callable $next): ?LegacyResponse
    {
        return null;
    }
}

final class ImplicitHeaderMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        return $next($request)->withHeader('X-Test-Middleware', 'yes');
    }
}

final class ImplicitMemberController
{
    #[Get('/all')]
    public function index(Request $request): Response
    {
        return Response::text('members');
    }

    #[Get('/detail/{id}')]
    public function show(Request $request, int $id): Response
    {
        return Response::json(['id' => $id]);
    }

    #[LegacyGet('/legacy-request')]
    public function legacyRequest(LegacyRequest $request): LegacyResponse
    {
        return LegacyResponse::text('legacy-request:' . $request->getMethod());
    }

    #[LegacyGet('/legacy')]
    public function legacy(): Response
    {
        return Response::text('legacy');
    }

    #[Get('/slug/{name}')]
    public function slug(string $name): Response
    {
        return Response::text('slug:' . $name);
    }

    #[Get('/reordered/{first}/{second}')]
    public function reordered(string $second, string $first): Response
    {
        return Response::text($second . ':' . $first);
    }

    #[Get('/request-after/{id}')]
    public function requestAfter(int $id, Request $request): Response
    {
        return Response::text($id . ':' . $request->getMethod());
    }

    #[Get('/mixed/{id}/{slug}')]
    public function mixed(string $label, int $id): Response
    {
        return Response::text($label . ':' . $id);
    }

    #[Post('/', ['auth'])]
    public function store(Request $request): Response
    {
        return Response::ok();
    }
}
