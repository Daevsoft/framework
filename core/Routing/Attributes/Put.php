<?php

declare(strict_types=1);

namespace Ds\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Put extends RouteAttribute
{
    public function __construct(string $path = '/', array|string|null $middleware = null)
    {
        parent::__construct('PUT', $path, $middleware);
    }
}
