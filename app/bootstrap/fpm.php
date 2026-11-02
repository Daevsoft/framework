<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Http\Kernel as AppKernel;
use App\Support\RouteRegistry;
use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Lifecycle\LifecycleManager;
use Ds\Middleware\MiddlewareStack;
use Ds\Routing\Router;

$router = new Router();
RouteRegistry::setRouter($router);

require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$container = new Container();
$lifecycle = new LifecycleManager();
$container->instance(LifecycleManager::class, $lifecycle);
$container->instance(MiddlewareStack::class, new MiddlewareStack());
$lifecycle->emit('booting');
$lifecycle->emit('booted');
$kernel = new AppKernel($router, $container);
$container->beginRequest();

try {
    $context = $container->make(Context::class);
    $request = Request::capture();
    $context->set('request', $request);
    $lifecycle->emit('request.received', $request);

    $response = $kernel->handle($request);
    $response->send();
} catch (\Throwable $exception) {
    error_log((string) $exception);
    Response::text('Internal Server Error', 500)->send();
} finally {
    try {
        $lifecycle->emit('request.handled', ['request' => $request ?? null]);
    } catch (\Throwable $exception) {
        error_log((string) $exception);
    } finally {
        $container->endRequest();
    }
}
