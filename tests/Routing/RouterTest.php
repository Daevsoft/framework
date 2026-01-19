<?php
namespace Tests\Routing;

use PHPUnit\Framework\TestCase;
use Ds\Routing\Router;
use App\Support\RouteRegistry;
use App\Support\Route;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        $router = new Router();
        \App\Support\RouteRegistry::setRouter($router);
    }

    public function testAddAndMatchBasicRoute()
    {
        $router = new Router();
        $router->add('GET', '/ping', function () { return 'pong'; });

        $route = $router->match('GET', '/ping');
        $this->assertNotNull($route);
        $this->assertSame('GET', $route->method);
        $this->assertSame('/ping', $route->path);
        $this->assertIsCallable($route->handler);
        $this->assertEquals('pong', ($route->handler)());
    }

    public function testDslRouteRegistration()
    {
        $router = new Router();
        \App\Support\RouteRegistry::setRouter($router);

        Route::get('/health', function(){ return 'ok'; });

        $route = $router->match('GET', '/health');
        $this->assertNotNull($route);
        $this->assertSame('/health', $route->path);
        $this->assertIsCallable($route->handler);
    }
}
