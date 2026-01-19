<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        
        if ($value === null) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('response')){
    /**
     * Create a response instance
     */
    function response($content = '', int $status = 200, array $headers = []): \Ds\Http\Response {
        return new \Ds\Http\Response($status, $headers, $content);
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config(string $key, $default = null) {
        static $configs = [];
        
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        if (!isset($configs[$file])) {
            $configPath = dirname(__DIR__) . "/config/{$file}.php";
            if (!file_exists($configPath)) {
                return $default;
            }
            $configs[$file] = require $configPath;
        }
        
        $value = $configs[$file];
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
}

if (!function_exists('app_path')) {
    /**
     * Get application path
     */
    function app_path(string $path = ''): string {
        $base = dirname(__DIR__);
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get base path
     */
    function base_path(string $path = ''): string {
        $base = dirname(__DIR__, 2);
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('public_path')) {
    /**
     * Get public path
     */
    function public_path(string $path = ''): string {
        $base = dirname(__DIR__, 2) . '/public';
        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$args): never {
        foreach ($args as $arg) {
            var_dump($arg);
        }
        die();
    }
}

if (!function_exists('dump')) {
    /**
     * Dump
     */
    function dump(...$args) {
        foreach ($args as $arg) {
            var_dump($arg);
        }
    }
}

// Global helper functions.


