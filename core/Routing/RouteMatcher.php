<?php

declare(strict_types=1);

namespace Ds\Routing;

class RouteMatcher
{
    public function match(string $method, string $path, array $routes): ?Route
    {
        foreach ($routes as $route) {
            if ($route->method === $method && $route->path === $path) {
                return $route;
            }
        }

        return null;
    }
}


