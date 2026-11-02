<?php

declare(strict_types=1);

namespace Ds\Routing;

/**
 * Backwards-compatible matcher facade.
 *
 * Router::match() owns the optimized lookup path. This class remains available
 * for callers that used the original matcher directly.
 */
final class RouteMatcher
{
    public function match(string $method, string $path, array $routes): ?Route
    {
        $method = strtoupper($method);
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        if ($path === '//') {
            $path = '/';
        }

        foreach ($routes as $route) {
            if (!$route instanceof Route || $route->method !== $method) {
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
}
