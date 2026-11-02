<?php

declare(strict_types=1);

namespace Ds\Context;

/**
 * Request context backed by Swoole coroutine-local storage when available.
 * The local fallback is intended for non-coroutine runtimes such as FPM.
 */
final class Context
{
    private static ?self $default = null;

    public static function default(): self
    {
        return self::$default ??= new self();
    }

    private const COROUTINE_KEY = 'ds.framework.context';
    protected array $fallbackStore = [];

    public function set(string $key, mixed $value): void
    {
        $coroutine = $this->coroutineContext();
        if ($coroutine !== null) {
            $store = $coroutine[self::COROUTINE_KEY] ?? [];
            $store[$key] = $value;
            $coroutine[self::COROUTINE_KEY] = $store;
            return;
        }

        $this->fallbackStore[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $coroutine = $this->coroutineContext();
        $store = $coroutine !== null
            ? ($coroutine[self::COROUTINE_KEY] ?? [])
            : $this->fallbackStore;

        return array_key_exists($key, $store) ? $store[$key] : $default;
    }

    public function has(string $key): bool
    {
        $coroutine = $this->coroutineContext();
        $store = $coroutine !== null
            ? ($coroutine[self::COROUTINE_KEY] ?? [])
            : $this->fallbackStore;

        return array_key_exists($key, $store);
    }

    public function remove(string $key): void
    {
        $coroutine = $this->coroutineContext();
        if ($coroutine !== null) {
            $store = $coroutine[self::COROUTINE_KEY] ?? [];
            unset($store[$key]);
            $coroutine[self::COROUTINE_KEY] = $store;
            return;
        }

        unset($this->fallbackStore[$key]);
    }

    public function all(): array
    {
        $coroutine = $this->coroutineContext();
        return $coroutine !== null
            ? ($coroutine[self::COROUTINE_KEY] ?? [])
            : $this->fallbackStore;
    }

    public function clear(): void
    {
        $coroutine = $this->coroutineContext();
        if ($coroutine !== null) {
            unset($coroutine[self::COROUTINE_KEY]);
            return;
        }

        $this->fallbackStore = [];
    }

    private function coroutineContext(): mixed
    {
        $coroutineClass = '\\Swoole\\Coroutine';
        if (!class_exists($coroutineClass) || $coroutineClass::getCid() < 0) {
            return null;
        }

        return $coroutineClass::getContext();
    }
}
