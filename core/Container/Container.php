<?php

declare(strict_types=1);

namespace Ds\Container;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    protected Bindings $bindings;
    protected array $instances = [];
    private static array $constructorCache = [];

    public function __construct(?Bindings $bindings = null)
    {
        $this->bindings = $bindings ?? new Bindings();
    }

    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        $this->bindings->set($abstract, $concrete ?? $abstract, $shared);
        unset($this->instances[$abstract]);
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete ?? $abstract, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->bindings->set($abstract, $instance, true);
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters, []);
    }

    private function resolve(string $abstract, array $parameters, array $resolving): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (isset($resolving[$abstract])) {
            throw new RuntimeException('Circular dependency detected: ' . implode(' -> ', [...array_keys($resolving), $abstract]));
        }

        $binding = $this->bindings->get($abstract);
        $concrete = $binding['concrete'] ?? $abstract;
        $shared = $binding['shared'] ?? false;
        $resolving[$abstract] = true;

        if (is_object($concrete) && !$concrete instanceof \Closure) {
            $object = $concrete;
        } elseif ($concrete instanceof \Closure || is_callable($concrete)) {
            $object = $concrete($this, $parameters);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $object = $this->build($concrete, $parameters, $resolving);
        } else {
            throw new RuntimeException("Unable to resolve {$abstract}");
        }

        if ($shared) {
            $this->instances[$abstract] = $object;
        }
        return $object;
    }

    private function build(string $class, array $parameters, array $resolving): object
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable");
        }

        if ($reflection->getConstructor() === null) {
            return new $class();
        }

        $arguments = [];
        foreach (self::constructorParameters($class, $reflection) as $parameter) {
            if (array_key_exists($parameter['name'], $parameters)) {
                $arguments[] = $parameters[$parameter['name']];
            } elseif ($parameter['type'] !== null && class_exists($parameter['type'])) {
                $arguments[] = $this->resolve($parameter['type'], [], $resolving);
            } elseif ($parameter['hasDefault']) {
                $arguments[] = $parameter['default'];
            } elseif ($parameter['nullable']) {
                $arguments[] = null;
            } else {
                throw new RuntimeException('Cannot resolve parameter ' . $parameter['name'] . ' for ' . $class);
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private static function constructorParameters(string $class, ReflectionClass $reflection): array
    {
        if (isset(self::$constructorCache[$class])) {
            return self::$constructorCache[$class];
        }

        $metadata = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            $metadata[] = [
                'type' => $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null,
                'name' => $parameter->getName(),
                'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                'hasDefault' => $parameter->isDefaultValueAvailable(),
                'nullable' => $parameter->allowsNull(),
            ];
        }

        return self::$constructorCache[$class] = $metadata;
    }

    public function has(string $abstract): bool
    {
        return $this->bindings->has($abstract) || class_exists($abstract);
    }

    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }
}
