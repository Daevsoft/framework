<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

class CorsMiddleware
{
    /**
     * Handle CORS headers
     */
    public function handle(Request $request, $next): Response
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Origin', env('CORS_ORIGIN', '*'));
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}


