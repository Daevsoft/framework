<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Ds\Auth\AuthManager;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\MiddlewareInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ?AuthManager $auth = null,
        private readonly string $guard = 'default'
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        $guard = ($this->auth ?? new AuthManager())->guard($this->guard);
        if (!$guard->check($request)) {
            return (new Response(401, ['Content-Type' => 'application/json; charset=utf-8', 'WWW-Authenticate' => 'Bearer'], '{"message":"Unauthenticated."}'));
        }

        $request->add('auth.identity', $guard->user());
        return $next($request);
    }
}
