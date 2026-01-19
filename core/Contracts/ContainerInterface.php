<?php

namespace Core\Contracts;

interface ContainerInterface
{
    /**
     * Bind a class/interface to a resolver
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void;

    /**
     * Register a singleton binding
     */
    public function singleton(string $abstract, $concrete = null): void;

    /**
     * Resolve a binding from the container
     */
    public function resolve(string $abstract, array $parameters = []);

    /**
     * Check if binding exists
     */
    public function has(string $abstract): bool;

    /**
     * Get a binding (alias for resolve)
     */
    public function get(string $abstract);

    /**
     * Set a scope for container resolution
     */
    public function setScope(string $scope): void;

    /**
     * Get current scope
     */
    public function getScope(): string;

    /**
     * Call a function with dependency injection
     */
    public function call(callable $callback, array $parameters = []);
}

