<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Ds App'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'runtime' => env('APP_RUNTIME', 'fpm'), // 'fpm' or 'swoole'
    'providers' => [
        // Service providers to boot on startup
    ],
];


