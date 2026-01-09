<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

use Ds\Broadcasting\Channels\Channel;

class Broadcaster
{
    public function broadcast(Channel $channel, string $event, mixed $payload = null): void
    {
        // Kirim event ke channel.
    }
}


