<?php

declare(strict_types=1);

namespace Ds\Events;

class EventManager
{
    protected array $listeners = [];

    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function emit(string $event, mixed $payload = null): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(new Event($event, $payload));
        }
    }
}


