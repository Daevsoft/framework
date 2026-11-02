<?php

declare(strict_types=1);

namespace Ds\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
abstract class RouteAttribute
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array|string|null $middleware = null
    ) {
    }
}
