<?php
namespace Ds\App;

class App {
    public static function bootOnce(): self {
        static $booted = false;
        if (!$booted) {
            // Boot logic here
            $booted = true;
        }
        return new self();
    }

    public function loadEnv(): self {
        // Load environment variables
        return $this;
    }

    public function loadConfig(): self {
        // Load configuration files
        return $this;
    }

    public function loadProviders(): self {
        // Load service providers
        return $this;
    }

    public function loadRoutes(): self {
        // Load application routes
        return $this;
    }

    public function startServer(): self {
        // Start the server (could be Swoole or fallback)
        return $this;
    }
}
