<?php

declare(strict_types=1);

namespace Ds\Broadcasting\Connections;

use Closure;
use Ds\Broadcasting\WebSocketHub;
use Throwable;

final class WebSocketConnection
{
    private array $listeners = [];
    private array $channels = [];
    private bool $closed = false;

    public function __construct(
        private readonly object $server,
        private readonly int $fd,
        private readonly WebSocketHub $hub
    ) {}

    public function id(): int { return $this->fd; }

    public function on(string $event, callable $listener): self
    {
        $event = strtolower($event);
        if (!in_array($event, ['connect', 'message', 'disconnect'], true)) {
            throw new \InvalidArgumentException("Unsupported WebSocket event [{$event}].");
        }
        $this->listeners[$event][] = Closure::fromCallable($listener);
        return $this;
    }

    public function dispatch(string $event, mixed $payload = null): void
    {
        foreach ($this->listeners[strtolower($event)] ?? [] as $listener) {
            try { $listener($payload, $this); }
            catch (Throwable $exception) { error_log((string) $exception); }
        }
    }

    public function send(string $message): bool
    {
        if ($this->closed || !method_exists($this->server, 'push')) return false;
        try { return $this->server->push($this->fd, $message) === true; }
        catch (Throwable $exception) { error_log((string) $exception); return false; }
    }

    public function subscribe(string $channel): self
    {
        $channel = trim($channel);
        if ($channel === '') throw new \InvalidArgumentException('Channel name cannot be empty.');
        $this->hub->subscribe($this->fd, $channel);
        return $this;
    }

    public function addChannel(string $channel): void { $this->channels[$channel] = true; }
    public function isSubscribedTo(string $channel): bool { return isset($this->channels[$channel]); }

    public function close(int $code = 1000, string $reason = ''): bool
    {
        if ($this->closed) return false;
        $this->closed = true;
        try {
            if (method_exists($this->server, 'disconnect')) return $this->server->disconnect($this->fd, $code, $reason) === true;
            if (method_exists($this->server, 'close')) return $this->server->close($this->fd) === true;
        } catch (Throwable $exception) { error_log((string) $exception); }
        return false;
    }

    public function markClosed(): void { $this->closed = true; }
}
