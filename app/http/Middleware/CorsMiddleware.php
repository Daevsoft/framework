<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Handle CORS headers
     */
    public function handle(Request $request, $next): Response
    {
        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', (string) env('CORS_ORIGIN', '*'))
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}


