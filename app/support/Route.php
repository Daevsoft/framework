<?php

declare(strict_types=1);

namespace App\Support;

use Ds\Routing\Router;

final class Route
{
    private static string $prefix = '';

    private static function resolveRouter(): Router
    {
        $router = RouteRegistry::getRouter();
        if ($router === null) {
            $router = new Router();
            RouteRegistry::setRouter($router);
        }
        return $router;
    }

    private static function register(string $method, string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add($method, $fullPath === '//' ? '/' : $fullPath, $handler);
    }

    public static function get(string $path, mixed $handler): void { self::register('GET', $path, $handler); }
    public static function post(string $path, mixed $handler): void { self::register('POST', $path, $handler); }
    public static function put(string $path, mixed $handler): void { self::register('PUT', $path, $handler); }
    public static function patch(string $path, mixed $handler): void { self::register('PATCH', $path, $handler); }
    public static function delete(string $path, mixed $handler): void { self::register('DELETE', $path, $handler); }
    public static function options(string $path, mixed $handler): void { self::register('OPTIONS', $path, $handler); }
    public static function ws(string $path, mixed $handler): void { self::register('WS', $path, $handler); }

    public static function prefix(string $prefix, callable $callback): void
    {
        $previous = self::$prefix;
        self::$prefix = $previous . '/' . trim($prefix, '/');

        try {
            $callback();
        } finally {
            self::$prefix = $previous;
        }
    }

    public static function group(string $prefix, callable $callback): void
    {
        self::prefix($prefix, $callback);
    }

    public static function resource(string $name, string $controller): void
    {
        self::prefix('/' . trim($name, '/'), static function () use ($controller): void {
            self::get('', "{$controller}@index");
            self::get('/create', "{$controller}@create");
            self::post('', "{$controller}@store");
            self::get('/{id}', "{$controller}@show");
            self::get('/{id}/edit', "{$controller}@edit");
            self::put('/{id}', "{$controller}@update");
            self::delete('/{id}', "{$controller}@destroy");
        });
    }
}
