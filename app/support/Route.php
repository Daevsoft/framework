<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\RouteRegistry;
use Ds\Routing\Router;

class Route
{
    /** @var string */
    private static string $prefix = '';

    private static function resolveRouter(): Router
    {
        $router = RouteRegistry::getRouter();
        if ($router === null) {
            $router = new \Ds\Routing\Router();
            RouteRegistry::setRouter($router);
        }
        return $router;
    }

    public static function get(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('GET', $fullPath, $handler);
    }

    public static function post(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('POST', $fullPath, $handler);
    }

    public static function put(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('PUT', $fullPath, $handler);
    }

    public static function patch(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('PATCH', $fullPath, $handler);
    }

    public static function delete(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('DELETE', $fullPath, $handler);
    }

    public static function options(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('OPTIONS', $fullPath, $handler);
    }

    public static function ws(string $path, mixed $handler): void
    {
        $fullPath = self::$prefix . $path;
        $fullPath = '/' . trim($fullPath, '/');
        self::resolveRouter()->add('WS', $fullPath, $handler);
    }

    public static function prefix(string $prefix, callable $callback): void
    {
        $old = self::$prefix;
        self::$prefix = $old . $prefix;
        $callback();
        self::$prefix = $old;
    }

    public static function group(string $prefix, callable $callback): void
    {
        self::prefix($prefix, $callback);
    }

    public static function resource(string $name, string $controller): void
    {
        $prefix = '/' . trim($name, '/');
        self::prefix($prefix, function() use ($controller) {
            // List all
            self::get('', "{$controller}@index");
            // Show form to create
            self::get('/create', "{$controller}@create");
            // Store new resource
            self::post('', "{$controller}@store");
            // Show single
            self::get('/{id}', "{$controller}@show");
            // Show edit form
            self::get('/{id}/edit', "{$controller}@edit");
            // Update resource
            self::put('/{id}', "{$controller}@update");
            // Delete resource
            self::delete('/{id}', "{$controller}@destroy");
        });
    }
}
