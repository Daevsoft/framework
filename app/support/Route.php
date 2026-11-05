<?php

declare(strict_types=1);

namespace App\Support;

use Ds\Routing\Attributes\RouteAttribute;
use Ds\Routing\Router;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class Route
{
    private static string $prefix = '';
    /** @var list<string> */
    private static array $middlewares = [];

    private static function resolveRouter(): Router
    {
        $router = RouteRegistry::getRouter();
        if ($router === null) {
            $router = new Router();
            RouteRegistry::setRouter($router);
        }
        return $router;
    }

    private static function register(string $method, string $path, mixed $handler): \Ds\Routing\Route
    {
        $fullPath = self::$prefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        $route = self::resolveRouter()->add($method, $fullPath === '//' ? '/' : $fullPath, $handler);

        if (self::$middlewares !== []) {
            $route->middleware(self::$middlewares);
        }

        return $route;
    }

    public static function get(string $path, mixed $handler): \Ds\Routing\Route { return self::register('GET', $path, $handler); }
    public static function post(string $path, mixed $handler): \Ds\Routing\Route { return self::register('POST', $path, $handler); }
    public static function put(string $path, mixed $handler): \Ds\Routing\Route { return self::register('PUT', $path, $handler); }
    public static function patch(string $path, mixed $handler): \Ds\Routing\Route { return self::register('PATCH', $path, $handler); }
    public static function delete(string $path, mixed $handler): \Ds\Routing\Route { return self::register('DELETE', $path, $handler); }
    public static function options(string $path, mixed $handler): \Ds\Routing\Route { return self::register('OPTIONS', $path, $handler); }
    public static function head(string $path, mixed $handler): \Ds\Routing\Route { return self::register('HEAD', $path, $handler); }
    public static function ws(string $path, mixed $handler): \Ds\Routing\Route { return self::register('WS', $path, $handler); }

    /** Generate a URL for a named route registered during application bootstrap. */
    public static function url(string $name, array $parameters = [], bool $absolute = false, ?string $baseUrl = null): string
    {
        return self::resolveRouter()->urlFor($name, $parameters, $absolute, $baseUrl);
    }

    public static function fallback(mixed $handler): \Ds\Routing\Route
    {
        $route = self::resolveRouter()->setFallback($handler);
        if (self::$middlewares !== []) {
            $route->middleware(self::$middlewares);
        }
        return $route;
    }

    public static function prefix(string $prefix, callable $callback): void
    {
        $previous = self::$prefix;
        self::$prefix = rtrim($previous, '/') . '/' . trim($prefix, '/');

        try {
            $callback();
        } finally {
            self::$prefix = $previous;
        }
    }

    public static function group(string $prefix, callable|string $callback): void
    {
        if (is_string($callback)) {
            self::prefix($prefix, static fn () => self::registerController($callback));
            return;
        }

        self::prefix($prefix, $callback);
    }

    public static function middleware(array|string $middleware, callable $callback): void
    {
        $previous = self::$middlewares;
        $items = is_array($middleware) ? $middleware : [$middleware];
        self::$middlewares = [...$previous, ...$items];

        try {
            $callback();
        } finally {
            self::$middlewares = $previous;
        }
    }

    /**
     * Discover route attributes once at bootstrap. No controller instance or
     * reflection is created while serving requests.
     */
    public static function registerController(string $controller): void
    {
        if (!class_exists($controller)) {
            throw new RuntimeException("Controller class {$controller} was not found.");
        }

        $reflection = new ReflectionClass($controller);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            foreach ($method->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                /** @var RouteAttribute $definition */
                $definition = $attribute->newInstance();
                $route = self::register(
                    $definition->method,
                    $definition->path,
                    [$controller, $method->getName()]
                );

                if ($definition->middleware !== null) {
                    $route->middleware($definition->middleware);
                }
            }
        }
    }

    public static function resource(string $name, string $controller, string $parameter = 'id'): ResourceRouteRegistrar
    {
        return self::registerResourceRoutes($name, $controller, false, $parameter);
    }

    public static function apiResource(string $name, string $controller, string $parameter = 'id'): ResourceRouteRegistrar
    {
        return self::registerResourceRoutes($name, $controller, true, $parameter);
    }

    /** @internal */
    private static function registerResourceRoutes(string $name, string $controller, bool $apiOnly, string $parameter): ResourceRouteRegistrar
    {
        if (trim($name, '/') === '' || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            throw new \InvalidArgumentException('Resource name must be a non-empty route path.');
        }
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $parameter) !== 1) {
            throw new \InvalidArgumentException('Resource parameter must be a valid route parameter name.');
        }

        $router = self::resolveRouter();
        $baseName = str_replace('/', '.', trim($name, '/'));
        $routes = [];

        self::prefix('/' . trim($name, '/'), static function () use ($controller, $apiOnly, $baseName, $parameter, &$routes): void {
            $routes['index'][] = self::get('', "{$controller}@index")->name($baseName . '.index');
            if (!$apiOnly) {
                $routes['create'][] = self::get('/create', "{$controller}@create")->name($baseName . '.create');
            }
            $routes['store'][] = self::post('', "{$controller}@store")->name($baseName . '.store');
            $routes['show'][] = self::get('/{' . $parameter . '}', "{$controller}@show")->name($baseName . '.show');
            if (!$apiOnly) {
                $routes['edit'][] = self::get('/{' . $parameter . '}/edit', "{$controller}@edit")->name($baseName . '.edit');
            }
            $routes['update'][] = self::put('/{' . $parameter . '}', "{$controller}@update")->name($baseName . '.update');
            $routes['update'][] = self::patch('/{' . $parameter . '}', "{$controller}@update");
            $routes['destroy'][] = self::delete('/{' . $parameter . '}', "{$controller}@destroy")->name($baseName . '.destroy');
        });

        return new ResourceRouteRegistrar($router, $routes);
    }
}
