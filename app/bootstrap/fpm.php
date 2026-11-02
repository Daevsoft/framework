<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Http\Kernel as AppKernel;
use App\Support\RouteRegistry;
use Ds\Container\Container;
use Ds\Http\Request;
use Ds\Routing\Router;

$router = new Router();
RouteRegistry::setRouter($router);

require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$container = new Container();
$container->singleton(Context::class);
$kernel = new AppKernel($router, $container);
$context = $container->make(Context::class);
$request = Request::capture();
$context->set('request', $request);

try {
    $response = $kernel->handle($request);
    $response->send();
} finally {
    $context->clear();
}
