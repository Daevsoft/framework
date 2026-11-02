<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): ?Response;
}
