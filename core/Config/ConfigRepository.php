<?php

declare(strict_types=1);

namespace Ds\Config;

use RuntimeException;
use Throwable;

final class ConfigRepository
{
    private static ?array $configs = null;

    public static function get(
        string $key,
        mixed $default = null,
        ?string $configDirectory = null,
        ?string $cacheFile = null
    ): mixed {
        self::load($configDirectory, $cacheFile);

        $parts = explode('.', $key);
        $file = array_shift($parts);
        $value = self::$configs[$file] ?? null;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value ?? $default;
    }

    public static function cache(?string $configDirectory = null, ?string $cacheFile = null): array
    {
        $directory = $configDirectory ?? self::defaultConfigDirectory();
        $target = $cacheFile ?? self::defaultCacheFile();
        $configs = [];

        foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            $value = require $file;
            if (!is_array($value)) {
                throw new RuntimeException("Configuration file {$file} must return an array.");
            }
            $configs[$name] = $value;
        }

        $output = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($configs, true) . ";\n";
        $parent = dirname($target);
        if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
            throw new RuntimeException("Unable to create configuration cache directory: {$parent}");
        }

        $temporary = $target . '.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($temporary, $output, LOCK_EX) === false || !@rename($temporary, $target)) {
            @unlink($temporary);
            throw new RuntimeException("Unable to write configuration cache: {$target}");
        }

        self::$configs = $configs;
        return $configs;
    }

    public static function clear(?string $cacheFile = null): bool
    {
        self::$configs = null;
        $target = $cacheFile ?? self::defaultCacheFile();
        return !is_file($target) || unlink($target);
    }

    public static function reset(): void
    {
        self::$configs = null;
    }

    private static function load(?string $configDirectory, ?string $cacheFile): void
    {
        if (self::$configs !== null) {
            return;
        }

        $cache = $cacheFile ?? self::defaultCacheFile();
        if (is_file($cache)) {
            try {
                $cached = require $cache;
                if (is_array($cached)) {
                    self::$configs = $cached;
                    return;
                }
            } catch (Throwable) {
                // Fall back to source configuration if a stale cache is corrupt.
            }
        }

        $directory = $configDirectory ?? self::defaultConfigDirectory();
        self::$configs = [];
        foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $value = require $file;
            if (is_array($value)) {
                self::$configs[basename($file, '.php')] = $value;
            }
        }
    }

    private static function defaultConfigDirectory(): string
    {
        return dirname(__DIR__, 2) . '/app/config';
    }

    private static function defaultCacheFile(): string
    {
        return dirname(__DIR__, 2) . '/app/storage/cache/config.php';
    }
}
