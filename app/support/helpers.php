<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => trim($value, " \"'")
        };
    }
}

if (!function_exists('response')) {
    /**
     * response() returns the response factory; response($body, ...) creates
     * a concrete response. Both fluent and direct usage remain lightweight.
     */
    function response(
        mixed $content = null,
        int $status = 200,
        array $headers = []
    ): \Ds\Http\Response|\Ds\Support\ResponseFactory {
        if (func_num_args() === 0) {
            return new \Ds\Support\ResponseFactory();
        }

        return new \Ds\Http\Response($status, $headers, (string) $content);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];

        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($configs[$file])) {
            $configPath = dirname(__DIR__) . "/config/{$file}.php";
            if (!is_file($configPath)) {
                return $default;
            }
            $configs[$file] = require $configPath;
        }

        $value = $configs[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        $base = dirname(__DIR__);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2) . '/public';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$args): never
    {
        foreach ($args as $arg) {
            var_dump($arg);
        }
        exit(1);
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$args): void
    {
        foreach ($args as $arg) {
            var_dump($arg);
        }
    }
}
