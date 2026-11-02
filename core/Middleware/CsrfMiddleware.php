<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Security\Csrf;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $token = $request->getHeader('x-csrf-token')
            ?? $request->getInput('_token')
            ?? $request->getInput('csrf_token');

        if (!Csrf::verify($token)) {
            return Response::text('CSRF token mismatch.', 419);
        }

        return $next($request);
    }
}
