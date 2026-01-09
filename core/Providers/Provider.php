<?php

declare(strict_types=1);

namespace Ds\Providers;

abstract class Provider
{
    public function register(): void
    {
        // Registrasi binding/container.
    }

    public function boot(): void
    {
        // Hook setelah semua provider register.
    }
}


