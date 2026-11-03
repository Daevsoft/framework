<?php

declare(strict_types=1);

namespace Ds\Lifecycle;

class LifecycleManager
{
    public function __construct(
        protected LifecycleRegistry $registry = new LifecycleRegistry()
    ) {
    }

    protected array $listeners = [];

    public function register(string $event, callable $listener): void
    {
        if (! $this->registry->has($event)) {
            throw new \InvalidArgumentException("Invalid lifecycle: {$event}");
        }
        $this->listeners[$event][] = $listener;
    }

    /** Remove a registered callback so long-lived workers can release it. */
    public function unregister(string $event, callable $listener): bool
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
        if (! $this->registry->has($event)) {
            return;
        }
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(new LifecycleEvent($event, $payload));
        }
    }
}


