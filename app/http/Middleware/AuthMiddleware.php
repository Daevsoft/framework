<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Http\Request;
use Ds\Http\Response;

class AuthMiddleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, $next): Response
    {
        // TODO: Implement authentication logic
        // This middleware can check for auth tokens, sessions, etc.
        
        return $next($request);
    }
}


