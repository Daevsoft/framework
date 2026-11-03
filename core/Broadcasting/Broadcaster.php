<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

use Ds\Broadcasting\Channels\Channel;

final class Broadcaster
{
    public function __construct(private readonly WebSocketHub $hub) {}

    /** Returns the number of connected subscribers that accepted the frame. */
    public function broadcast(Channel $channel, string $event, mixed $payload = null): int
    {
        if ($event === '') throw new \InvalidArgumentException('Broadcast event name cannot be empty.');
        return $this->hub->broadcast($channel->name, $event, $payload);
    }
}
