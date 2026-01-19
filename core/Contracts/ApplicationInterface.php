<?php

namespace Core\Contracts;

interface ApplicationInterface
{
    /**
     * Get the container instance
     */
    public function getContainer(): ContainerInterface;

    /**
     * Register a service provider
     */
    public function registerProvider($provider): void;

    /**
     * Boot all providers
     */
    public function boot(): void;

    /**
     * Check if application is in development mode
     */
    public function isDevelopment(): bool;

    /**
     * Get application path
     */
    public function basePath(string $path = ''): string;

    /**
     * Get configuration value
     */
    public function config(string $key, $default = null);

    /**
     * Get environment variable
     */
    public function env(string $key, $default = null);

    /**
     * Resolve class from container
     */
    public function make(string $abstract, array $parameters = []);

    /**
     * Check if application has booted
     */
    public function hasBooted(): bool;
}

