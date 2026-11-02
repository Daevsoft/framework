<?php

declare(strict_types=1);

namespace Tests\Routing;

use App\Support\Route as RouteDsl;
use App\Support\RouteRegistry;
use Ds\Http\Request;
use Ds\Http\RequestDispatcher;
use Ds\Http\Response;
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
        self::assertSame('{\"id\":42}', $response->body());
    }

    public function testRegistersMultipleHttpMethodsAndAttributeMiddleware(): void
    {
        RouteDsl::group('/members', ImplicitMemberController::class);

        $route = RouteRegistry::getRouter()?->match('POST', '/members');
        self::assertNotNull($route);
        self::assertSame(['auth'], $route->middlewares);
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

    #[Post('/', ['auth'])]
    public function store(Request $request): Response
    {
        return Response::ok();
    }
}
