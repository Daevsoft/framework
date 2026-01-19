<?php

require __DIR__ . '/../../vendor/autoload.php';

use Ds\Http\Request;
use App\Http\Kernel as AppKernel;
use App\Support\RouteRegistry;
use Ds\Routing\Router;

// Load routes first so the DSL populates the registry
require __DIR__ . '/../routes/web.php';

$router = RouteRegistry::getRouter();
if ($router === null) {
    $router = new Router();
    RouteRegistry::setRouter($router);
}

$kernel = new AppKernel($router);

$response = $kernel->handle(
    Request::capture()
);

$response->send();
