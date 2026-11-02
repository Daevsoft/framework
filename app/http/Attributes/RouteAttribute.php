<?php

declare(strict_types=1);

namespace App\Http\Attributes;

use Attribute;
use Ds\Routing\Attributes\RouteAttribute as BaseRouteAttribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class RouteAttribute extends BaseRouteAttribute
{
    public function __construct(string $method, string $path, array|string|null $middleware = null)
    {
        parent::__construct($method, $path, $middleware);
    }
}
