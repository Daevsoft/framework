<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Container\Container;
use InvalidArgumentException;
use RuntimeException;

final class MiddlewareRegistry
{
    /** @var array<string, class-string<MiddlewareInterface>> */
    private static array $aliases = [
        'session' => SessionMiddleware::class,
        'csrf' => CsrfMiddleware::class,
    ];

    public static function alias(string $name, string $middleware): void
    {
        if ($name === '' || !class_exists($middleware)) {
            throw new InvalidArgumentException('Middleware alias requires a name and an existing class.');
        }

        if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
            throw new InvalidArgumentException("Middleware {$middleware} must implement MiddlewareInterface.");
        }

        self::$aliases[$name] = $middleware;
    }

    public static function resolve(string $name, ?Container $container = null): MiddlewareInterface
    {
        $class = self::$aliases[$name] ?? $name;
        if (!class_exists($class) || !is_subclass_of($class, MiddlewareInterface::class)) {
            throw new RuntimeException("Middleware [{$name}] is not registered or does not implement MiddlewareInterface.");
        }

        $instance = $container !== null ? $container->make($class) : new $class();
        if (!$instance instanceof MiddlewareInterface) {
            throw new RuntimeException("Unable to resolve middleware [{$name}].");
        }

        return $instance;
    }

    public static function clear(): void
    {
        self::$aliases = [
            'session' => SessionMiddleware::class,
            'csrf' => CsrfMiddleware::class,
        ];
    }
}
