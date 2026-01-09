<?php

declare(strict_types=1);

namespace Ds\Routing;

class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public mixed $handler
    ) {
    }
}


