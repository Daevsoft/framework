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

    private ?Route $fallbackRoute = null;

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
        $route->onDefinitionChanged(function (Route $changed): void { $this->refreshRoute($changed); });

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
        if ($this->fallbackRoute !== null) {
            $routes[] = $this->fallbackRoute;
        }
        return $routes;
    }

    public function match(string $method, string $path): ?Route
    {
        $method = strtoupper(trim($method));
        $path = self::normalizeLookupPath($path);
        if ($path === null) {
            return null;
        }

        $route = $this->matchMethod($method, $path);
        if ($route !== null) {
            return $route;
        }

        // RFC-compatible HEAD fallback: execute the GET handler, but let the
        // dispatcher/emitter suppress the response body for the HEAD request.
        if ($method !== 'HEAD') {
            return null;
        }
        $getRoute = $this->matchMethod('GET', $path);
        return $getRoute?->matched($getRoute->params, 'HEAD');
    }

    /** @return list<string> Methods explicitly registered for this path. */
    public function allowedMethods(string $path): array
    {
        $path = self::normalizeLookupPath($path);
        if ($path === null) {
            return [];
        }
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

    public function remove(Route $route): bool
    {
        $removed = false;
        if (isset($this->staticRoutes[$route->method][$route->path])
            && $this->staticRoutes[$route->method][$route->path] === $route) {
            unset($this->staticRoutes[$route->method][$route->path]);
            $removed = true;
        }
        foreach ($this->dynamicRoutes[$route->method] ?? [] as $index => $compiled) {
            if ($compiled['route'] === $route) {
                array_splice($this->dynamicRoutes[$route->method], $index, 1);
                $removed = true;
                break;
            }
        }
        foreach ($this->namedRoutes as $name => $named) {
            if ($named === $route) {
                unset($this->namedRoutes[$name]);
            }
        }
        $route->onDefinitionChanged(null);
        return $removed;
    }

    public function setFallback(mixed $handler): Route
    {
        if ($this->fallbackRoute !== null) {
            throw new InvalidArgumentException('Only one fallback route may be registered.');
        }
        if (!is_callable($handler) && !(is_array($handler) && count($handler) === 2)
            && !(is_string($handler) && str_contains($handler, '@'))) {
            throw new InvalidArgumentException('Fallback handler must be callable or a controller handler.');
        }
        $this->fallbackRoute = new Route('FALLBACK', '*', $handler);
        return $this->fallbackRoute;
    }

    public function fallbackRoute(): ?Route
    {
        return $this->fallbackRoute;
    }

    public function hasPath(string $path): bool
    {
        return $this->allowedMethods($path) !== [];
    }

    public function isValidPath(string $path): bool
    {
        return self::normalizeLookupPath($path) !== null;
    }

    /**
     * Generate a URL from a registered route name. Unused parameters become
     * a query string; required path parameters must be supplied explicitly.
     *
     * @param array<string, scalar> $parameters
     */
    public function urlFor(string $name, array $parameters = [], bool $absolute = false, ?string $baseUrl = null): string
    {
        $route = $this->namedRoutes[$name] ?? null;
        if ($route === null) {
            throw new InvalidArgumentException("Named route [{$name}] is not registered.");
        }

        $used = [];
        $path = preg_replace_callback('/\\{([A-Za-z_][A-Za-z0-9_]*)\\}/', function (array $matches) use ($route, $parameters, &$used): string {
            $parameter = $matches[1];
            if (!array_key_exists($parameter, $parameters) || !is_scalar($parameters[$parameter])) {
                throw new InvalidArgumentException("Missing or invalid route parameter [{$parameter}].");
            }
            $value = (string) $parameters[$parameter];
            if (str_contains($value, '/') || str_contains($value, '\\')
                || preg_match('/%(?:2f|5c|2e|0[0-9a-f]|1[0-9a-f]|7f)/i', $value) === 1
                || $value === '.' || $value === '..'
                || preg_match('/[\\x00-\\x1F\\x7F]/', $value) === 1) {
                throw new InvalidArgumentException("Value for route parameter [{$parameter}] must be a single safe path segment.");
            }
            if (isset($route->constraints[$parameter])
                && preg_match('~^(?:' . $route->constraints[$parameter] . ')$~D', $value) !== 1) {
                throw new InvalidArgumentException("Value for route parameter [{$parameter}] violates its constraint.");
            }
            $used[$parameter] = true;
            return rawurlencode($value);
        }, $route->path);

        $query = array_diff_key($parameters, $used);
        if ($query !== []) {
            $path .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        if (!$absolute) {
            return $path;
        }
        if ($baseUrl === null || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || !in_array(strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
            || parse_url($baseUrl, PHP_URL_HOST) === null
            || parse_url($baseUrl, PHP_URL_USER) !== null
            || parse_url($baseUrl, PHP_URL_PASS) !== null
            || parse_url($baseUrl, PHP_URL_QUERY) !== null
            || parse_url($baseUrl, PHP_URL_FRAGMENT) !== null) {
            throw new InvalidArgumentException('Absolute route URLs require a valid HTTP(S) base URL.');
        }
        return rtrim($baseUrl, '/') . $path;
    }

    /** @return array<string, Route> */
    public function namedRoutes(): array
    {
        return $this->namedRoutes;
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
            $group = 'p' . $index;
            // Capture exactly one URL segment. The separately validated constraint
            // is applied to the decoded value after the route regex matches.
            $pattern .= '(?P<' . $group . '>[^/]+)';
            $names[$name] = $group;
            $cursor = $offset + strlen($token);
        }
        $pattern .= preg_quote(substr($path, $cursor), '~');
        $compiledPattern = '~^' . $pattern . '$~D';
        // PHP's PCRE engine compiles lazily; warming the generated expression
        // here keeps first-use compilation out of the request matching path.
        if (@preg_match($compiledPattern, '') === false) {
            throw new InvalidArgumentException("Unable to compile route pattern [{$route->path}].");
        }

        return ['route' => $route, 'pattern' => $compiledPattern, 'names' => $names];
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
            $existing = $this->namedRoutes[$route->routeName] ?? null;
            if ($existing !== null && $existing !== $route) {
                throw new InvalidArgumentException("Duplicate route name [{$route->routeName}].");
            }
            foreach ($this->namedRoutes as $name => $registered) {
                if ($registered === $route && $name !== $route->routeName) {
                    unset($this->namedRoutes[$name]);
                }
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
            || preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $path) === 1
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
            $constraintsMatch = true;
            foreach ($compiled['names'] as $name => $group) {
                $value = rawurldecode((string) ($matches[$group] ?? ''));
                if (preg_match('/%(?:2f|5c|2e|0[0-9a-f]|1[0-9a-f]|7f)/i', $value) === 1 || $value === '.' || $value === '..'
                    || str_contains($value, '\\') || preg_match('/[\\x00-\\x1F\\x7F]/', $value) === 1) {
                    return null;
                }
                $constraint = $compiled['route']->constraints[$name] ?? null;
                if ($constraint !== null && preg_match('~^(?:' . $constraint . ')$~D', $value) !== 1) {
                    $constraintsMatch = false;
                    break;
                }
                $params[$name] = $value;
            }
            if (!$constraintsMatch) {
                continue;
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

    private static function normalizeLookupPath(string $path): ?string
    {
        try {
            return self::normalizePath(parse_url($path, PHP_URL_PATH) ?: '/');
        } catch (\Throwable) {
            return null;
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

    public function get(string $path, mixed $handler): Route { return $this->add('GET', $path, $handler); }
    public function post(string $path, mixed $handler): Route { return $this->add('POST', $path, $handler); }
    public function put(string $path, mixed $handler): Route { return $this->add('PUT', $path, $handler); }
    public function patch(string $path, mixed $handler): Route { return $this->add('PATCH', $path, $handler); }
    public function delete(string $path, mixed $handler): Route { return $this->add('DELETE', $path, $handler); }
    public function options(string $path, mixed $handler): Route { return $this->add('OPTIONS', $path, $handler); }
    public function head(string $path, mixed $handler): Route { return $this->add('HEAD', $path, $handler); }
    public function ws(string $path, mixed $handler): Route { return $this->add('WS', $path, $handler); }
}
