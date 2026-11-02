<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\MiddlewareInterface;

/**
 * Fail-closed placeholder. Replace with a configured authenticator before
 * registering this middleware on protected routes.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        return Response::text('Authentication middleware is not configured.', 501);
    }
}
