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


