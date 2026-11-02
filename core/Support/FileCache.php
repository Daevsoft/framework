<?php

declare(strict_types=1);

namespace Ds\Support;

use RuntimeException;

final class FileCache
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create cache directory: {$directory}");
        }
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->read($key);
        return $entry === null ? $default : $entry['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 0): bool
    {
        if ($ttlSeconds < 0) {
            throw new \InvalidArgumentException('Cache TTL cannot be negative.');
        }

        try {
            $payload = json_encode([
                'expires_at' => $ttlSeconds === 0 ? null : time() + $ttlSeconds,
                'value' => $value,
            ], JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Cache values must be JSON-serializable.', 0, $exception);
        }

        $target = $this->path($key);
        $temporary = $target . '.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temporary, $payload, LOCK_EX) === false) {
            return false;
        }

        if (!@rename($temporary, $target)) {
            @unlink($temporary);
            return false;
        }

        return true;
    }

    public function remember(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $entry = $this->read($key);
        if ($entry !== null) {
            return $entry['value'];
        }

        $value = $resolver();
        $this->put($key, $value, $ttlSeconds);
        return $value;
    }

    public function forget(string $key): bool
    {
        $path = $this->path($key);
        return !is_file($path) || unlink($path);
    }

    public function clear(): int
    {
        $removed = 0;
        foreach (glob(rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (is_file($file) && unlink($file)) {
                $removed++;
            }
        }
        return $removed;
    }

    private function read(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        try {
            $entry = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            @unlink($path);
            return null;
        }

        if (!is_array($entry) || !array_key_exists('value', $entry)) {
            @unlink($path);
            return null;
        }

        $expiresAt = $entry['expires_at'] ?? null;
        if (is_int($expiresAt) && $expiresAt <= time()) {
            @unlink($path);
            return null;
        }

        return $entry;
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.json';
    }
}
