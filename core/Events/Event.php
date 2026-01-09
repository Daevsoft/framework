<?php

declare(strict_types=1);

namespace Ds\Events;

class Event
{
    public function __construct(
        public string $name,
        public mixed $payload = null
    ) {
    }
}


