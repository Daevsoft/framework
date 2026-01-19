<?php

declare(strict_types=1);

namespace Ds\Container;

use Ds\Container\Bindings;

class Container
{
    protected Bindings $bindings;
    protected array $instances = [];

    public function __construct(Bindings $bindings = new Bindings())
    {
        $this->bindings = $bindings;
    }

    public function bind(string $abstract, mixed $concrete, bool $shared = false): void
    {
        $this->bindings->set($abstract, $concrete, $shared);
    }

    public function singleton(string $abstract, mixed $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->bindings->set($abstract, $instance, true);
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        $binding = $this->bindings->get($abstract);
        if (!$binding) {
            throw new \RuntimeException("Binding not found: {$abstract}");
        }

        if (($binding['shared'] ?? false) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $binding['concrete'];
        if (is_callable($concrete)) {
            $object = $concrete(...$parameters);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $ref = new \ReflectionClass($concrete);
            $ctor = $ref->getConstructor();
            if (is_null($ctor)) {
                $object = new $concrete();
            } else {
                $args = [];
                foreach ($ctor->getParameters() as $param) {
                    $dep = $param->getClass();
                    if ($dep) {
                        $args[] = $this->make($dep->getName());
                    } elseif (array_key_exists($param->getName(), $parameters)) {
                        $args[] = $parameters[$param->getName()];
                    } elseif ($param->allowsNull()) {
                        $args[] = null;
                    } else {
                        $args[] = null;
                    }
                }
                $object = $ref->newInstanceArgs($args);
            }
        } else {
            $object = $concrete;
        }

        if (($binding['shared'] ?? false)) {
            $this->instances[$abstract] = $object;
        }
        return $object;
    }
}
