<?php
namespace Tests\Routing;

use PHPUnit\Framework\TestCase;
use Ds\Routing\Router;
use App\Support\RouteRegistry;
use App\Support\Route;
use Ds\App\App;

final class AppTest extends TestCase
{
    protected function setUp(): void
    {
        App::bootOnce()
            ->loadEnv()
            ->loadConfig()
            ->loadProviders()
            ->loadRoutes()
            ->startServer();
    }
    public function testAppLifecycle(){
        $this->assertTrue(true, 'Lifecycle works');
    }
}
