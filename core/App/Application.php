<?php

declare(strict_types=1);

namespace Ds\App;

/**
 * Application core: bertugas melakukan boot sekali dan mengelola lifecycle.
 */
class Application
{
    protected bool $booted = false;

    public function bootOnce(): void
    {
        if ($this->booted) {
            return;
        }

        $this->boot();
        $this->booted = true;
    }

    protected function boot(): void
    {
        // Registrasi provider, konfigurasi container, dsb.
    }
}


