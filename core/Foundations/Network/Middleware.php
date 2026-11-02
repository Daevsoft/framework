<?php

declare(strict_types=1);

namespace Ds\Foundations\Network;

use Ds\Middleware\MiddlewareInterface;

abstract class Middleware implements MiddlewareInterface
{
    public array $options = [];

    public function handle(Request $request, callable $next): ?Response
    {
        return $next($request);
    }
}
