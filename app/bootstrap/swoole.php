<?php

declare(strict_types=1);

use App\Http\Kernel as AppKernel;
use App\Support\RouteRegistry;
use Ds\Container\Container;
use Ds\Routing\Router;
use Ds\Server\SwooleServer;

$router = new Router();
RouteRegistry::setRouter($router);

// Register routes once during worker bootstrap, not on every HTTP request.
require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$container = new Container();
$container->instance(LifecycleManager::class, new LifecycleManager());
$kernel = new AppKernel($router, $container);

$host = (string) env('SERVER_HOST', '127.0.0.1');
$port = (int) env('SERVER_PORT', 8000);

(new SwooleServer($container, $host, $port, $kernel))->start();
