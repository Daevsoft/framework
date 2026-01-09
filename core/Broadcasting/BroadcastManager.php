<?php

declare(strict_types=1);

namespace Ds\Broadcasting;

class BroadcastManager
{
    public function connection(string $name = null): Broadcaster
    {
        // Kembalikan broadcaster default/bernama.
        return new Broadcaster();
    }
}


