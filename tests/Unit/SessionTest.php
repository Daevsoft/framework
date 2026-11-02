<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
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

    public function testDestroyExpiresTheSessionCookie(): void
    {
        $session = SessionManager::start(new Request(), $this->directory);
        $session->destroy();

        self::assertStringContainsString('Max-Age=0', $session->cookieHeader());
    }
}
