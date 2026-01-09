<?php

declare(strict_types=1);

namespace Ds\Providers;

class ProviderRepository
{
    protected array $providers = [];

    public function add(Provider $provider): void
    {
        $this->providers[] = $provider;
    }

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $provider->register();
        }
    }

    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
}


