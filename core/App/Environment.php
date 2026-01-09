<?php

declare(strict_types=1);

namespace Ds\App;

class Environment
{
    public function __construct(
        protected array $variables = []
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
}


