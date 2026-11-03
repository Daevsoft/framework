<?php

declare(strict_types=1);

namespace Ds\Auth;

use InvalidArgumentException;

final class AuthManager
{
    private array $providers = [];

    public function __construct(private readonly ?array $configuration = null) {}

    public function registerProvider(string $name, callable $resolver): void
    {
        if (trim($name) === '') throw new InvalidArgumentException('Auth provider name cannot be empty.');
        $this->providers[$name] = $resolver;
    }

    public function guard(string $name = 'default'): Guard
    {
        $config = $this->configuration ?? (function_exists('config') ? config('auth', []) : []);
        if ($name === 'default') $name = (string) ($config['default'] ?? 'default');
        $guard = $config['guards'][$name] ?? null;
        if (!is_array($guard)) return new Guard();

        $provider = (string) ($guard['provider'] ?? '');
        $providerConfig = $config['providers'][$provider] ?? [];
        $resolver = $this->providers[$provider] ?? ($providerConfig['resolver'] ?? null);
        return new Guard(is_callable($resolver) ? $resolver : null, (string) ($guard['driver'] ?? 'token'));
    }
}
