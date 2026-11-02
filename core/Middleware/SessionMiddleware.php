<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Session\SessionManager;

final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ?string $directory = null)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $session = SessionManager::start(
            $request,
            $this->directory ?? base_path('app/storage/sessions'),
            (string) env('SESSION_COOKIE', 'DSF_SESSID'),
            (int) env('SESSION_LIFETIME', 120) * 60
        );

        $context = Context::default();
        $context->set('session', $session);

        try {
            $response = $next($request);
        } finally {
            try {
                $session->save();
            } finally {
                $context->remove('session');
            }
        }

        $cookie = $session->cookieHeader();
        return $cookie === null ? $response : $response->withHeader('Set-Cookie', $cookie);
    }
}
