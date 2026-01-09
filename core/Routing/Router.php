<?php

declare(strict_types=1);

namespace Ds\Routing;

class Router
{
    protected array $routes = [];

    public function add(string $method, string $path, mixed $handler): void
    {
        $this->routes[] = new Route($method, $path, $handler);
    }

    public function match(string $method, string $path): ?Route
    {
        return (new RouteMatcher())->match($method, $path, $this->routes);
    }
}


