<?php

declare(strict_types=1);

namespace Ds\Routing;

final class Router
{
    /** @var array<string, array<string, Route>> */
    protected array $staticRoutes = [];

    /** @var array<string, list<array{route:Route, pattern:string, names:array<int,string>}>> */
    protected array $dynamicRoutes = [];

    public function add(string $method, string $path, mixed $handler): Route
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);
        $route = new Route($method, $path, $handler);

        if (!str_contains($path, '{')) {
            $this->staticRoutes[$method][$path] = $route;
            return $route;
        }

        $names = [];
        $quoted = preg_quote($path, '~');
        $pattern = preg_replace_callback(
            '~\\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\\}~',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];
                return '([^/]+)';
            },
            $quoted
        );

        $this->dynamicRoutes[$method][] = [
            'route' => $route,
            'pattern' => '~^' . $pattern . '$~D',
            'names' => $names,
        ];

        return $route;
    }

    /** @return list<Route> */
    public function all(): array
    {
        $routes = [];
        foreach ($this->staticRoutes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $routes[] = $route;
            }
        }
        foreach ($this->dynamicRoutes as $methodRoutes) {
            foreach ($methodRoutes as $compiled) {
                $routes[] = $compiled['route'];
            }
        }

        return $routes;
    }

    public function match(string $method, string $path): ?Route
    {
        $method = strtoupper($method);
        $path = self::normalizePath(parse_url($path, PHP_URL_PATH) ?: '/');

        if (isset($this->staticRoutes[$method][$path])) {
            return $this->staticRoutes[$method][$path];
        }

        foreach ($this->dynamicRoutes[$method] ?? [] as $compiled) {
            if (preg_match($compiled['pattern'], $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($compiled['names'] as $index => $name) {
                $params[$name] = rawurldecode($matches[$index]);
            }

            $route = $compiled['route'];
            return new Route($route->method, $route->path, $route->handler, $params);
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
