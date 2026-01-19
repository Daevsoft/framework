<?php

declare(strict_types=1);

return [
    'host' => env('SERVER_HOST', '127.0.0.1'),
    'port' => env('SERVER_PORT', 8000),
    
    'swoole' => [
        'worker_num' => env('SWOOLE_WORKERS', 4),
        'task_worker_num' => env('SWOOLE_TASK_WORKERS', 2),
        'enable_coroutine' => env('SWOOLE_COROUTINE', true),
        'max_request' => env('SWOOLE_MAX_REQUEST', 0),
        'max_conn' => env('SWOOLE_MAX_CONN', 1024),
    ],

    'websocket' => [
        'enabled' => env('WEBSOCKET_ENABLED', true),
        'port' => env('WEBSOCKET_PORT', 6001),
        'path' => env('WEBSOCKET_PATH', '/ws'),
    ],
];


