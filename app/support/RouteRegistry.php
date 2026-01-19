<?php

declare(strict_types=1);

namespace App\Support;

use Ds\Routing\Router;

class RouteRegistry
{
    protected static ?Router $router = null;

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    public static function getRouter(): ?Router
    {
        return self::$router;
    }
}
