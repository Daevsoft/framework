<?php

declare(strict_types=1);

namespace Ds\Lifecycle;

class LifecycleEvent
{
    public function __construct(
        public string $name,
        public mixed $payload = null
    ) {
    }
}


