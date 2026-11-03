<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\AuthMiddleware;
use Ds\Auth\AuthManager;
use Ds\Auth\Identity;
use Ds\Http\Request;
use Ds\Http\Response;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    public function testUnconfiguredAuthenticationFailsClosed(): void
    {
        $called = false;
        $response = (new AuthMiddleware())->handle(
            new Request(),
            static function () use (&$called): Response {
                $called = true;
                return Response::text('allowed');
            }
        );

        self::assertSame(401, $response->status());
        self::assertFalse($called);
        self::assertSame('Bearer', $response->headers()['WWW-Authenticate']);
    }

    public function testBearerResolverAuthenticatesAndAddsIdentityToRequest(): void
    {
        $auth = new AuthManager(['default' => 'token', 'guards' => ['token' => ['provider' => 'users']], 'providers' => []]);
        $auth->registerProvider('users', static fn (string $token) => $token === 'valid-token'
            ? new Identity('user-42', ['admin'])
            : null);

        $response = (new AuthMiddleware($auth))->handle(
            new Request([], ['Authorization' => 'Bearer valid-token']),
            static function (Request $request): Response {
                $identity = $request->getInput('auth.identity');
                return Response::text($identity instanceof Identity ? $identity->id : 'missing');
            }
        );

        self::assertSame(200, $response->status());
        self::assertSame('user-42', $response->body());
    }

    public function testSessionGuardDoesNotPretendToBeBearerAuthentication(): void
    {
        $auth = new AuthManager([
            'default' => 'session',
            'guards' => ['session' => ['driver' => 'session', 'provider' => 'users']],
            'providers' => [],
        ]);
        $auth->registerProvider('users', static fn () => ['id' => 'user-1']);

        $response = (new AuthMiddleware($auth, 'session'))->handle(
            new Request([], ['Authorization' => 'Bearer token']),
            static fn (): Response => Response::text('must not run')
        );

        self::assertSame(401, $response->status());
    }

    public function testInvalidBearerTokenIsRejected(): void
    {
        $auth = new AuthManager(['default' => 'token', 'guards' => ['token' => ['provider' => 'users']], 'providers' => []]);
        $auth->registerProvider('users', static fn () => null);

        $response = (new AuthMiddleware($auth))->handle(
            new Request([], ['Authorization' => 'Bearer invalid']),
            static fn (): Response => Response::text('must not run')
        );

        self::assertSame(401, $response->status());
    }
}
