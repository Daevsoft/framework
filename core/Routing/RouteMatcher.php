<?php

declare(strict_types=1);

namespace Ds\Routing;

/**
 * Compatibility facade for callers that still use RouteMatcher directly.
 */
final class RouteMatcher
{
    public function match(string $method, string $path, array $routes): ?Route
    {
        $router = new Router();
        foreach ($routes as $route) {
            if ($route instanceof Route) {
                $router->add($route->method, $route->path, $route->handler);
            }
        }

        return $router->match($method, $path);
    }
}
