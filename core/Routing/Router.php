<?php

declare(strict_types=1);

namespace Ds\Routing;

use InvalidArgumentException;

final class Router
{
    /** @var array<string, array<string, Route>> */
    protected array $staticRoutes = [];

    /** @var array<string, list<array{route:Route, pattern:string, names:array<string,string>}>> */
    protected array $dynamicRoutes = [];

    /** @var array<string, Route> */
    protected array $namedRoutes = [];

    /** @var array<string, string> */
    private const METHODS = ['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH',
        'DELETE' => 'DELETE', 'OPTIONS' => 'OPTIONS', 'HEAD' => 'HEAD', 'WS' => 'WS'];

    public function add(string $method, string $path, mixed $handler): Route
    {
        $method = strtoupper(trim($method));
        if (!isset(self::METHODS[$method])) {
            throw new InvalidArgumentException("Unsupported route method [{$method}].");
        }
        if (!is_callable($handler) && !(is_array($handler) && count($handler) === 2)
            && !(is_string($handler) && str_contains($handler, '@'))) {
            throw new InvalidArgumentException('Route handler must be callable or a controller handler.');
        }

        $path = self::normalizePath($path);
        $this->assertValidPlaceholders($path);
        if ($this->findDefinition($method, $path) !== null) {
            throw new InvalidArgumentException("Duplicate route definition [{$method} {$path}].");
        }

        $route = new Route($method, $path, $handler);
        $route->onDefinitionChanged(fn (Route $changed): mixed => $this->refreshRoute($changed));

        if (!str_contains($path, '{')) {
            $this->staticRoutes[$method][$path] = $route;
            return $route;
        }

        $this->dynamicRoutes[$method][] = $this->compile($route);
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
        $method = strtoupper(trim($method));
        $path = self::normalizePath(parse_url($path, PHP_URL_PATH) ?: '/');

        $route = $this->matchMethod($method, $path);
        if ($route !== null) {
            return $route;
        }

        // RFC-compatible HEAD fallback: execute the GET handler, but let the
        // dispatcher/emitter suppress the response body for the HEAD request.
        return $method === 'HEAD' ? $this->matchMethod('GET', $path)?->matched(
            $this->matchMethod('GET', $path)->params,
            'HEAD'
        ) : null;
    }

    /** @return list<string> Methods explicitly registered for this path. */
    public function allowedMethods(string $path): array
    {
        $path = self::normalizePath(parse_url($path, PHP_URL_PATH) ?: '/');
        $allowed = [];
        foreach (array_keys(self::METHODS) as $method) {
            if ($method === 'WS') {
                continue;
            }
            if ($this->matchMethod($method, $path) !== null) {
                $allowed[] = $method;
            }
        }
        if (in_array('GET', $allowed, true) && !in_array('HEAD', $allowed, true)) {
            $allowed[] = 'HEAD';
        }
        if ($allowed !== [] && !in_array('OPTIONS', $allowed, true)) {
            $allowed[] = 'OPTIONS';
        }
        sort($allowed);
        return $allowed;
    }

    public function hasPath(string $path): bool
    {
        return $this->allowedMethods($path) !== [];
    }

    /** @return array{route:Route,pattern:string,names:array<string,string>} */
    private function compile(Route $route): array
    {
        $path = $route->path;
        $names = [];
        $cursor = 0;
        $pattern = '';
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => [$token, $offset]) {
            $name = $matches[1][$index][0];
            $pattern .= preg_quote(substr($path, $cursor, $offset - $cursor), '~');
            $constraint = $route->constraints[$name] ?? '[^/]+';
            $group = 'p' . $index;
            $pattern .= '(?P<' . $group . '>(?:' . $constraint . '))';
            $names[$name] = $group;
            $cursor = $offset + strlen($token);
        }
        $pattern .= preg_quote(substr($path, $cursor), '~');

        return ['route' => $route, 'pattern' => '~^' . $pattern . '$~D', 'names' => $names];
    }

    private function refreshRoute(Route $route): void
    {
        if (str_contains($route->path, '{')) {
            foreach ($this->dynamicRoutes[$route->method] ?? [] as $index => $compiled) {
                if ($compiled['route'] === $route) {
                    $this->dynamicRoutes[$route->method][$index] = $this->compile($route);
                    break;
                }
            }
        }
        if ($route->routeName !== null) {
            foreach ($this->namedRoutes as $name => $registered) {
                if ($registered === $route && $name !== $route->routeName) {
                    unset($this->namedRoutes[$name]);
                }
            }
            $existing = $this->namedRoutes[$route->routeName] ?? null;
            if ($existing !== null && $existing !== $route) {
                throw new InvalidArgumentException("Duplicate route name [{$route->routeName}].");
            }
            $this->namedRoutes[$route->routeName] = $route;
        }
    }

    private function findDefinition(string $method, string $path): ?Route
    {
        if (isset($this->staticRoutes[$method][$path])) {
            return $this->staticRoutes[$method][$path];
        }
        foreach ($this->dynamicRoutes[$method] ?? [] as $compiled) {
            if ($compiled['route']->path === $path) {
                return $compiled['route'];
            }
        }
        return null;
    }

    private function matchMethod(string $method, string $path): ?Route
    {
        if (str_contains($path, '%2f') || str_contains($path, '%2F')
            || preg_match('/%(?![A-Fa-f0-9]{2})/', $path) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return null;
        }

        if (isset($this->staticRoutes[$method][$path])) {
            return $this->staticRoutes[$method][$path]->matched([]);
        }

        foreach ($this->dynamicRoutes[$method] ?? [] as $compiled) {
            if (preg_match($compiled['pattern'], $path, $matches) !== 1) {
                continue;
            }
            $params = [];
            foreach ($compiled['names'] as $name => $group) {
                $params[$name] = rawurldecode((string) ($matches[$group] ?? ''));
            }
            return $compiled['route']->matched($params);
        }

        return null;
    }

    private function assertValidPlaceholders(string $path): void
    {
        $withoutValid = preg_replace('/\{[A-Za-z_][A-Za-z0-9_]*\}/', '', $path);
        if (str_contains((string) $withoutValid, '{') || str_contains((string) $withoutValid, '}')) {
            throw new InvalidArgumentException("Malformed route parameter in [{$path}].");
        }
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $matches);
        if (count($matches[1]) !== count(array_unique($matches[1]))) {
            throw new InvalidArgumentException("Duplicate route parameter name in [{$path}].");
        }
    }

    private static function normalizePath(string $path): string
    {
        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new InvalidArgumentException('Route path must be a non-empty path without control characters.');
        }
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = preg_replace('#/+#', '/', '/' . trim($path, '/')) ?? '/';
        return $path === '' ? '/' : $path;
    }

    public function get(string $path, mixed $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, mixed $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, mixed $handler): void { $this->add('PUT', $path, $handler); }
    public function patch(string $path, mixed $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, mixed $handler): void { $this->add('DELETE', $path, $handler); }
    public function options(string $path, mixed $handler): void { $this->add('OPTIONS', $path, $handler); }
}
