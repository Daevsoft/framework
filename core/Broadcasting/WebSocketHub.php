<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

use Ds\Broadcasting\Connections\WebSocketConnection;
use RuntimeException;

/**
 * Worker-local WebSocket connection registry.
 *
 * This hub intentionally makes no cross-worker delivery guarantee. Use a
 * shared pub/sub transport before deploying broadcasts across multiple workers.
 */
final class WebSocketHub
{
    private static ?self $default = null;

    /** @var array<int, WebSocketConnection> */
    private array $connections = [];

    public static function default(): self
    {
        return self::$default ??= new self();
    }

    public static function replaceDefault(?self $hub): void
    {
        self::$default = $hub;
    }

    public function add(WebSocketConnection $connection): void
    {
        $this->connections[$connection->id()] = $connection;
    }

    public function remove(int $fd): void
    {
        unset($this->connections[$fd]);
    }

    public function get(int $fd): ?WebSocketConnection
    {
        return $this->connections[$fd] ?? null;
    }

    public function subscribe(int $fd, string $channel): void
    {
        $connection = $this->get($fd);
        if ($connection === null) {
            throw new RuntimeException("WebSocket connection {$fd} is not registered.");
        }

        $connection->addChannel($channel);
    }

    public function broadcast(string $channel, string $event, mixed $payload = null): int
    {
        $message = json_encode([
            'channel' => $channel,
            'event' => $event,
            'data' => $payload,
        ], JSON_THROW_ON_ERROR);

        $sent = 0;
        foreach ($this->connections as $connection) {
            if ($connection->isSubscribedTo($channel) && $connection->send($message)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function count(): int
    {
        return count($this->connections);
    }

    public function clear(): void
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }
        $this->connections = [];
    }
}
