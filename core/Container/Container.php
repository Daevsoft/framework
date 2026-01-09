<?php

declare(strict_types=1);

namespace Ds\Container;

class Container
{
    public function __construct(
        protected Bindings $bindings = new Bindings()
    ) {
    }

    public function bind(string $abstract, mixed $concrete, bool $shared = false): void
    {
        $this->bindings->set($abstract, $concrete, $shared);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        $binding = $this->bindings->get($abstract);
        if (! $binding) {
            throw new \RuntimeException("Binding not found: {$abstract}");
        }

        $concrete = $binding['concrete'];
        if (is_callable($concrete)) {
            return $concrete(...$parameters);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return new $concrete(...$parameters);
        }

        return $concrete;
    }
}


