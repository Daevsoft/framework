<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Session\SessionManager;
use PHPUnit\Framework\TestCase;

final class LegacyRequestRouteHistoryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ds-route-history-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
        Context::default()->remove('request');
        Context::default()->remove('session');
    }

    protected function tearDown(): void
    {
        Context::default()->remove('request');
        Context::default()->remove('session');

        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testTracksOnlyTheLatestTwoLocalUrisAndReturnsBackResponse(): void
    {
        $session = SessionManager::start(new Request(), $this->directory);
        Context::default()->set('session', $session);

        $first = new Request([], [], 'GET', '/orders');
        $first->query = ['page' => 2];
        Context::default()->set('request', $first);
        Request::trackRoute();

        Context::default()->set('request', new Request([], [], 'GET', '/orders/42'));
        Request::trackRoute();

        Context::default()->set('request', new Request([], [], 'GET', '/orders/42/edit'));
        Request::trackRoute();

        $response = Request::getBack();

        self::assertSame(302, $response->status());
        self::assertSame('/orders/42', $response->headers()['Location']);
        self::assertFalse($session->has('track_route'), 'Reading route history must consume it.');
    }

    public function testSafelyReadsLegacySerializedRouteHistory(): void
    {
        $session = SessionManager::start(new Request(), $this->directory);
        $session->put('track_route', serialize(['/orders', '/orders/42']));
        Context::default()->set('session', $session);
        Context::default()->set('request', new Request([], [], 'GET', '/orders/42/edit'));

        self::assertSame('/orders', Request::getReferrer());
        self::assertFalse($session->has('track_route'));
    }

    public function testRejectsExternalOrProtocolRelativeRouteHistoryEntries(): void
    {
        $session = SessionManager::start(new Request(), $this->directory);
        Context::default()->set('session', $session);

        $this->expectException(\InvalidArgumentException::class);
        Request::trackRoute('//attacker.example/path');
    }

    public function testRouteHistoryRequiresSessionMiddleware(): void
    {
        $this->expectException(\RuntimeException::class);
        Request::getTrackRoute();
    }
}
