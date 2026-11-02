<?php

declare(strict_types=1);

namespace Ds\Container;

use Ds\Context\Context;
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
        $this->bindings->set(
            $abstract,
            $concrete ?? $abstract,
            $shared,
            $shared ? 'singleton' : 'transient'
        );
        unset($this->instances[$abstract]);
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete ?? $abstract, true);
    }

    public function scoped(string $abstract, mixed $concrete = null): void
    {
        $this->bindings->set($abstract, $concrete ?? $abstract, false, 'request');
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->bindings->set($abstract, $instance, true, 'singleton');
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters, []);
    }

    /**
     * Start a request scope. In Swoole, Context stores this state per coroutine.
     */
    public function beginRequest(): void
    {
        $this->requestContext()->clear();
    }

    /**
     * Always call from a finally block after the request has been handled.
     */
    public function endRequest(): void
    {
        $this->requestContext()->clear();
    }

    private function resolve(string $abstract, array $parameters, array $resolving): mixed
    {
        $binding = $this->bindings->get($abstract);
        $concrete = $binding['concrete'] ?? $abstract;
        $scope = $binding['scope'] ?? (($binding['shared'] ?? false) ? 'singleton' : 'transient');

        if ($scope === 'singleton' && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $requestCache = [];
        if ($scope === 'request') {
            $context = $this->requestContext();
            $requestCache = $context->get('container.request_instances', []);
            if (array_key_exists($abstract, $requestCache)) {
                return $requestCache[$abstract];
            }
        }

        if (isset($resolving[$abstract])) {
            throw new RuntimeException(
                'Circular dependency detected: ' . implode(' -> ', [...array_keys($resolving), $abstract])
            );
        }

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

        if ($scope === 'singleton') {
            $this->instances[$abstract] = $object;
        } elseif ($scope === 'request') {
            $requestCache[$abstract] = $object;
            $this->requestContext()->set('container.request_instances', $requestCache);
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

    private function requestContext(): Context
    {
        if (!isset($this->instances[Context::class])) {
            $this->instance(Context::class, new Context());
        }

        return $this->instances[Context::class];
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
