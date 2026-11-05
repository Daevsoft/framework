<?php

declare(strict_types=1);

namespace Ds\Routing;

/**
 * Compatibility facade for callers that still use RouteMatcher directly.
 *
 * Preserve the registered route metadata while adapting the legacy list-based
 * API to the current Router implementation.
 */
final class RouteMatcher
{
    /**
     * @param list<Route> $routes
     */
    public function match(string $method, string $path, array $routes): ?Route
    {
        $router = new Router();
        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                continue;
            }

            if ($route->method === 'FALLBACK') {
                $copy = $router->setFallback($route->handler);
            } else {
                $copy = $router->add($route->method, $route->path, $route->handler);
                foreach ($route->constraints as $parameter => $pattern) {
                    $copy->where($parameter, $pattern);
                }
                if ($route->routeName !== null) {
                    $copy->name($route->routeName);
                }
            }

            if ($route->middlewares !== []) {
                $copy->middleware($route->middlewares);
            }
        }

        return $router->match($method, $path);
    }
}
