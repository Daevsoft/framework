<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

use RuntimeException;

final class BroadcastManager
{
    public function __construct(private readonly ?array $configuration = null) {}

    public function connection(?string $name = null): Broadcaster
    {
        $config = $this->configuration ?? (function_exists('config') ? config('broadcasting', []) : []);
        $name ??= (string) ($config['default'] ?? 'websocket');
        $connection = ($config['connections'] ?? [])[$name] ?? ['driver' => $name];
        $driver = (string) ($connection['driver'] ?? $name);

        if (!in_array($driver, ['websocket', 'memory'], true)) {
            throw new RuntimeException("Broadcast driver [{$driver}] is not implemented; configure websocket or add a shared transport.");
        }

        return new Broadcaster(WebSocketHub::default());
    }
}
