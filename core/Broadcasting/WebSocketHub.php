<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

use Ds\Broadcasting\Connections\WebSocketConnection;
use RuntimeException;
use Throwable;

/**
 * Worker-local connection registry with optional Swoole worker IPC fan-out.
 *
 * Cross-worker messages use Server::sendMessage/PipeMessage; no Redis client
 * or per-request connection is retained. Each worker only stores its own peers.
 */
final class WebSocketHub
{
    private static ?self $default = null;

    /** @var array<int, WebSocketConnection> */
    private array $connections = [];

    public function __construct(
        private readonly ?object $server = null,
        private readonly ?int $workerId = null,
        private readonly int $workerCount = 1,
        private readonly int $maxBroadcastBytes = 65536
    ) {
    }

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

    /**
     * Broadcast locally and fan out to peer workers when running under Swoole.
     * The returned count is local accepted recipients; PipeMessage delivery is
     * best-effort and remote recipient counts are intentionally not guessed.
     */
    public function broadcast(string $channel, string $event, mixed $payload = null): int
    {
        $message = json_encode([
            'channel' => $channel,
            'event' => $event,
            'data' => $payload,
        ], JSON_THROW_ON_ERROR);

        if (strlen($message) > max(1024, $this->maxBroadcastBytes)) {
            throw new RuntimeException('WebSocket broadcast payload exceeds the configured IPC limit.');
        }

        $sent = $this->deliverFrame($message);
        if ($this->server !== null && $this->workerId !== null && $this->workerCount > 1
            && method_exists($this->server, 'sendMessage')) {
            for ($targetWorker = 0; $targetWorker < $this->workerCount; $targetWorker++) {
                if ($targetWorker === $this->workerId) {
                    continue;
                }

                try {
                    if ($this->server->sendMessage($message, $targetWorker) !== true) {
                        error_log("Unable to forward WebSocket broadcast to worker {$targetWorker}.");
                    }
                } catch (Throwable $exception) {
                    error_log((string) $exception);
                }
            }
        }

        return $sent;
    }

    /** Deliver a PipeMessage frame to this worker's local subscribers only. */
    public function deliverFrame(string $message): int
    {
        if (strlen($message) > max(1024, $this->maxBroadcastBytes)) {
            return 0;
        }

        try {
            $frame = json_decode($message, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return 0;
        }

        if (!is_array($frame)
            || !is_string($frame['channel'] ?? null)
            || !is_string($frame['event'] ?? null)
            || $frame['channel'] === ''
            || $frame['event'] === '') {
            return 0;
        }

        $sent = 0;
        foreach ($this->connections as $connection) {
            if ($connection->isSubscribedTo($frame['channel']) && $connection->send($message)) {
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
