<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Broadcasting\Broadcaster;
use Ds\Broadcasting\Channels\Channel;
use Ds\Broadcasting\Connections\WebSocketConnection;
use Ds\Broadcasting\WebSocketHub;
use PHPUnit\Framework\TestCase;

final class WebSocketBroadcastTest extends TestCase
{
    public function testBroadcastOnlySendsToSubscribedConnections(): void
    {
        $server = new FakeWebSocketServer();
        $hub = new WebSocketHub();
        $first = new WebSocketConnection($server, 10, $hub);
        $second = new WebSocketConnection($server, 11, $hub);
        $hub->add($first);
        $hub->add($second);
        $first->subscribe('orders.42');

        $sent = (new Broadcaster($hub))->broadcast(new Channel('orders.42'), 'OrderUpdated', ['status' => 'ready']);

        self::assertSame(1, $sent);
        self::assertSame([10], array_column($server->frames, 'fd'));
        self::assertSame([
            'channel' => 'orders.42',
            'event' => 'OrderUpdated',
            'data' => ['status' => 'ready'],
        ], json_decode($server->frames[0]['data'], true));
    }

    public function testRemovingConnectionStopsFutureBroadcasts(): void
    {
        $server = new FakeWebSocketServer();
        $hub = new WebSocketHub();
        $connection = new WebSocketConnection($server, 12, $hub);
        $hub->add($connection);
        $connection->subscribe('news');
        $hub->remove(12);

        self::assertSame(0, $hub->broadcast('news', 'Update'));
        self::assertSame(0, $hub->count());
    }

    public function testEmptyChannelAndEventAreRejected(): void
    {
        $server = new FakeWebSocketServer();
        $hub = new WebSocketHub();
        $connection = new WebSocketConnection($server, 13, $hub);
        $hub->add($connection);

        try {
            $connection->subscribe(' ');
            self::fail('Expected invalid channel exception.');
        } catch (\InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(\InvalidArgumentException::class);
        (new Broadcaster($hub))->broadcast(new Channel('news'), '');
    }
}

final class FakeWebSocketServer
{
    public array $frames = [];

    public function push(int $fd, string $data): bool
    {
        $this->frames[] = ['fd' => $fd, 'data' => $data];
        return true;
    }

    public function disconnect(int $fd, int $code = 1000, string $reason = ''): bool
    {
        return true;
    }
}
