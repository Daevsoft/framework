<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Middleware\CsrfMiddleware;
use Ds\Middleware\SessionMiddleware;
use Ds\Security\Csrf;
use Ds\Session\SessionManager;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-session-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        Context::default()->remove('session');
        unset($_ENV['APP_KEY']);

        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testSessionPersistsAcrossRequestsAndFlashDataExpiresAfterOneRead(): void
    {
        $first = SessionManager::start(new Request(), $this->directory);
        $first->put('user_id', 42);
        $first->flash('notice', 'Welcome');
        $first->save();

        $cookie = $first->cookieHeader();
        self::assertStringContainsString('HttpOnly', $cookie);
        self::assertStringContainsString('SameSite=Lax', $cookie);

        $second = SessionManager::start(
            new Request([], ['cookie' => $cookie], 'GET', '/'),
            $this->directory
        );

        self::assertSame(42, $second->get('user_id'));
        self::assertSame('Welcome', $second->getFlash('notice'));
        $second->save();

        $third = SessionManager::start(
            new Request([], ['cookie' => $cookie], 'GET', '/'),
            $this->directory
        );

        self::assertNull($third->getFlash('notice'));
        self::assertSame(42, $third->get('user_id'));
    }

    public function testDifferentSessionCookiesNeverShareData(): void
    {
        $first = SessionManager::start(new Request(), $this->directory);
        $first->put('role', 'admin');
        $first->save();

        $second = SessionManager::start(new Request(), $this->directory);
        self::assertNotSame($first->id(), $second->id());
        self::assertNull($second->get('role'));
    }

    public function testCsrfTokenIsBoundToCurrentSessionAndSecret(): void
    {
        $_ENV['APP_KEY'] = str_repeat('k', 32);
        $session = SessionManager::start(new Request(), $this->directory);
        Context::default()->set('session', $session);

        $token = Csrf::token();
        self::assertTrue(Csrf::verify($token));
        self::assertFalse(Csrf::verify('invalid-token'));
    }

    public function testSessionMiddlewarePersistsDataAndAddsCookie(): void
    {
        $middleware = new SessionMiddleware($this->directory);
        $first = $middleware->handle(
            new Request([], [], 'GET', '/'),
            static function (): Response {
                session(['user_id' => 77]);
                return Response::text('created');
            }
        );

        $cookie = $first->headers()['Set-Cookie'];
        self::assertStringContainsString('DSF_SESSID=', $cookie);

        $second = $middleware->handle(
            new Request([], ['cookie' => $cookie], 'GET', '/'),
            static fn (): Response => Response::text((string) session('user_id'))
        );

        self::assertSame('77', $second->body());
    }

    public function testCsrfMiddlewareRejectsUnsafeRequestsWithoutValidToken(): void
    {
        $_ENV['APP_KEY'] = str_repeat('s', 32);
        $session = SessionManager::start(new Request(), $this->directory);
        Context::default()->set('session', $session);
        $middleware = new CsrfMiddleware();
        $called = false;

        $rejected = $middleware->handle(
            new Request([], [], 'POST', '/submit'),
            static function () use (&$called): Response {
                $called = true;
                return Response::ok();
            }
        );

        self::assertSame(419, $rejected->status());
        self::assertFalse($called);

        $token = Csrf::token();
        $accepted = $middleware->handle(
            new Request([], ['x-csrf-token' => $token], 'POST', '/submit'),
            static fn (): Response => Response::ok()
        );

        self::assertSame(200, $accepted->status());
    }

    public function testDestroyExpiresTheSessionCookie(): void
    {
        $session = SessionManager::start(new Request(), $this->directory);
        $session->destroy();

        self::assertStringContainsString('Max-Age=0', $session->cookieHeader());
    }
}
