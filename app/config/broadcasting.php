<?php

declare(strict_types=1);

return [
    'default' => env('BROADCAST_DRIVER', 'redis'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', 'localhost'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_BROADCAST_DB', 1),
        ],
        'websocket' => [
            'driver' => 'websocket',
            'host' => env('WEBSOCKET_HOST', 'localhost'),
            'port' => env('WEBSOCKET_PORT', 6001),
        ],
        'sse' => [
            'driver' => 'sse',
        ],
    ],

    'channels' => [
        'presence' => [
            'url' => env('BROADCAST_PRESENCE_URL', '/broadcasting/auth'),
        ],
    ],
];


