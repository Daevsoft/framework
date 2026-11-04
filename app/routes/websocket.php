<?php

declare(strict_types=1);

use App\Support\Route;
use Ds\Broadcasting\Channels\Channel;
use Ds\Broadcasting\BroadcastManager;

// Example echo route. Add authentication/authorization before subscribing
// clients to private or presence channels in production.
Route::ws('/ws', function ($connection): void {
    $connection->on('connect', static function ($request, $peer): void {
        $peer->send(json_encode(['event' => 'connected', 'connection_id' => $peer->id()]));
    });

    $connection->on('message', static function (string $message, $peer): void {
        $peer->send(json_encode(['event' => 'echo', 'data' => $message]));
    });

    $connection->on('disconnect', static function (): void {
        // Release application-specific connection state here.
    });
});

// Multi-worker IPC smoke route is only registered in the test environment.
if (env('APP_ENV', 'production') === 'testing') {
    Route::ws('/__test/broadcast', function ($connection): void {
        $connection->subscribe('ci-cross-worker');
        $connection->on('connect', static function ($request, $peer): void {
            $peer->send(json_encode([
                'event' => 'connected',
                'connection_id' => $peer->id(),
                'worker_id' => $peer->workerId(),
            ]));
        });

        $connection->on('message', static function (string $message, $peer): void {
            $peer->send(json_encode(['event' => 'echo', 'data' => $message]));
            app(BroadcastManager::class)->connection()->broadcast(
                new Channel('ci-cross-worker'),
                'CiBroadcast',
                ['message' => $message]
            );
        });
    });
}

// Example server-side broadcast:
// app(BroadcastManager::class)->connection()->broadcast(
//     new Channel('announcements'),
//     'AnnouncementCreated',
//     ['message' => 'Hello']
// );
