<?php

declare(strict_types=1);

namespace Ds\Middleware;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Session\SessionManager;

final class SessionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $session = SessionManager::start(
            $request,
            base_path('app/storage/sessions'),
            (string) env('SESSION_COOKIE', 'DSF_SESSID'),
            (int) env('SESSION_LIFETIME', 120) * 60
        );

        $context = Context::default();
        $context->set('session', $session);

        try {
            $response = $next($request);
            $session->save();

            $cookie = $session->cookieHeader();
            return $cookie === null ? $response : $response->withHeader('Set-Cookie', $cookie);
        } finally {
            $context->remove('session');
        }
    }
}
