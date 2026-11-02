<?php

declare(strict_types=1);

namespace Ds\Routing;

final class Router
{
    /** @var list<Route> */
    protected array $routes = [];

    /** @var array<string, array<string, Route>> */
    protected array $staticRoutes = [];

    public function add(string $method, string $path, mixed $handler): void
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        // Cache exact routes for the common, parameter-free fast path.
        if (!str_contains($path, '{')) {
            $this->staticRoutes[$method][$path] = $route;
        }
    }

    public function match(string $method, string $path): ?Route
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);

        if (isset($this->staticRoutes[$method][$path])) {
            return $this->staticRoutes[$method][$path];
        }

        foreach ($this->routes as $route) {
            if ($route->method !== $method || !str_contains($route->path, '{')) {
                continue;
            }

            $names = [];
            $quoted = preg_quote($route->path, '~');
            $pattern = preg_replace_callback(
                '~\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}~',
                static function (array $matches) use (&$names): string {
                    $names[] = $matches[1];
                    return '([^/]+)';
                },
                $quoted
            );

            if ($pattern !== null && preg_match('~^' . $pattern . '$~D', $path, $matches) === 1) {
                array_shift($matches);
                $params = [];
                foreach ($names as $index => $name) {
                    $params[$name] = rawurldecode($matches[$index]);
                }

                return new Route($route->method, $route->path, $route->handler, $params);
            }
        }

        return null;
    }

    public function get(string $path, mixed $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, mixed $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, mixed $handler): void { $this->add('PUT', $path, $handler); }
    public function patch(string $path, mixed $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, mixed $handler): void { $this->add('DELETE', $path, $handler); }
    public function options(string $path, mixed $handler): void { $this->add('OPTIONS', $path, $handler); }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
