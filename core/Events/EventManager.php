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

    /** Remove a listener and release its captured object references. */
    public function off(string $event, callable $listener): bool
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }

        foreach ($this->listeners[$event] as $index => $registered) {
            if ($registered === $listener) {
                array_splice($this->listeners[$event], $index, 1);
                if ($this->listeners[$event] === []) {
                    unset($this->listeners[$event]);
                }
                return true;
            }
        }

        return false;
    }

    /** Remove listeners for one event, or all listeners when omitted. */
    public function clear(?string $event = null): void
    {
        if ($event === null) {
            $this->listeners = [];
            return;
        }

        unset($this->listeners[$event]);
    }

    public function emit(string $event, mixed $payload = null): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(new Event($event, $payload));
        }
    }
}


