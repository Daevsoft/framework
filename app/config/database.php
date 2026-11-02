<?php

declare(strict_types=1);

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', 3306),
    'database' => env('DB_NAME', ''),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'ssl_cert' => env('SSL_CERT', ''),
    'ssl_verify' => (bool) env('SSL_VERIFY', true),
    'pool' => [
        'max' => (int) env('DB_POOL_MAX', 10),
        'timeout' => (float) env('DB_POOL_TIMEOUT', 3.0),
    ],
];
