<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\MiddlewareInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $request->getMethod() === 'OPTIONS'
            ? new Response(204)
            : $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', (string) env('CORS_ORIGIN', '*'))
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '600')
            ->withHeader('Vary', 'Origin');
    }
}
