<?php

declare(strict_types=1);

namespace Ds\Lifecycle;

/**
 * Menyimpan daftar lifecycle event yang valid.
 */
class LifecycleRegistry
{
    protected array $lifecycles = [
        'booting',
        'booted',
        'request.received',
        'request.handled',
        'worker.start',
        'worker.stop',
    ];

    public function all(): array
    {
        return $this->lifecycles;
    }

    public function has(string $name): bool
    {
        return in_array($name, $this->lifecycles, true);
    }
}


