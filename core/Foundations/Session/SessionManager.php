<?php

declare(strict_types=1);

namespace Ds\Foundations\Session;

/**
 * Request-local compatibility facade. It deliberately does not hold static
 * session data, so Swoole workers cannot leak one user's state to another.
 */
final class SessionManager
{
    public static function init(): self
    {
        return new self();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return \session($key, $default);
    }

    public function put(string $key, mixed $value): self
    {
        \session([$key => $value]);
        return $this;
    }

    public function has(string $key): bool
    {
        return \session_manager()->has($key);
    }

    public function delete(string $key): void
    {
        \unsession($key);
    }

    public function destroy(): void
    {
        \session_manager()->destroy();
    }

    public function id(): string
    {
        return \session_manager()->id();
    }
}
