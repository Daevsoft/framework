<?php

declare(strict_types=1);

return [
    'host' => (string) env('SERVER_HOST', '127.0.0.1'),
    'port' => (int) env('SERVER_PORT', 8000),

    'swoole' => [
        'worker_num' => (int) env('SWOOLE_WORKERS', 4),
        'task_worker_num' => (int) env('SWOOLE_TASK_WORKERS', 0),
        'enable_coroutine' => (bool) env('SWOOLE_COROUTINE', true),
        'max_request' => (int) env('SWOOLE_MAX_REQUEST', 10000),
        'max_conn' => (int) env('SWOOLE_MAX_CONN', 1024),
        'package_max_length' => (int) env('SWOOLE_MAX_PACKAGE_SIZE', 2097152),
    ],

    'websocket' => [
        'enabled' => (bool) env('WEBSOCKET_ENABLED', true),
        'host' => (string) env('WEBSOCKET_HOST', '127.0.0.1'),
        'port' => (int) env('WEBSOCKET_PORT', env('SERVER_PORT', 8000)),
        'path' => (string) env('WEBSOCKET_PATH', '/ws'),
        'max_broadcast_payload_bytes' => (int) env('WEBSOCKET_MAX_BROADCAST_BYTES', 65536),
    ],
];
