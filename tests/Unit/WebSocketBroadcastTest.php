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

    public function testBroadcastFansOutAcrossWorkersWithoutRebroadcastingPipeFrames(): void
    {
        $sourceServer = new FakeWebSocketServer();
        $sourceHub = new WebSocketHub($sourceServer, 0, 2, 8192);
        $sourceConnection = new WebSocketConnection($sourceServer, 20, $sourceHub);
        $sourceHub->add($sourceConnection);
        $sourceConnection->subscribe('orders');

        $remoteServer = new FakeWebSocketServer();
        $remoteHub = new WebSocketHub($remoteServer, 1, 2, 8192);
        $remoteConnection = new WebSocketConnection($remoteServer, 21, $remoteHub);
        $remoteHub->add($remoteConnection);
        $remoteConnection->subscribe('orders');

        self::assertSame(1, $sourceHub->broadcast('orders', 'Updated', ['id' => 7]));
        self::assertCount(1, $sourceServer->messages);
        self::assertSame(1, $sourceServer->messages[0]['worker']);

        self::assertSame(1, $remoteHub->deliverFrame($sourceServer->messages[0]['message']));
        self::assertSame([21], array_column($remoteServer->frames, 'fd'));
        self::assertSame([], $remoteServer->messages, 'PipeMessage delivery must not fan out again.');
    }

    public function testBroadcastRejectsFramesAboveConfiguredIpcLimit(): void
    {
        $hub = new WebSocketHub(null, null, 1, 1024);
        $this->expectException(\RuntimeException::class);
        $hub->broadcast('large', 'Payload', str_repeat('x', 2048));
    }

    public function testClearDetachesRegistryBeforeInvokingCloseCallbacks(): void
    {
        $server = new FakeWebSocketServer();
        $hub = new WebSocketHub();
        $connection = new WebSocketConnection($server, 14, $hub);
        $hub->add($connection);
        $server->onDisconnect = static function () use ($hub): void {
            self::assertSame(0, $hub->count(), 'The registry must be detached before close callbacks run.');
        };

        $hub->clear();

        self::assertSame(0, $hub->count());
        self::assertSame([14], $server->disconnected);
        $hub->clear();
        self::assertSame([14], $server->disconnected, 'Repeated clear must be idempotent.');
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
    public array $messages = [];
    public array $disconnected = [];
    public ?\\Closure $onDisconnect = null;

    public function push(int $fd, string $data): bool
    {
        $this->frames[] = ['fd' => $fd, 'data' => $data];
        return true;
    }

    public function sendMessage(string $message, int $workerId): bool
    {
        $this->messages[] = ['message' => $message, 'worker' => $workerId];
        return true;
    }

    public function disconnect(int $fd, int $code = 1000, string $reason = ''): bool
    {
        $this->disconnected[] = $fd;
        ($this->onDisconnect)?->__invoke();
        return true;
    }
}
