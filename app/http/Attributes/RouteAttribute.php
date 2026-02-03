<?php
namespace App\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RouteAttribute
{
    public function __construct(public string $method, public string $path)
    {
    }
}
