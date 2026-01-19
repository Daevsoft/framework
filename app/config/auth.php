<?php

declare(strict_types=1);

return [
    'default' => env('AUTH_GUARD', 'token'),
    
    'guards' => [
        'token' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
        'session' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'memory', // or 'database'
        ],
    ],

    'passwords' => [
        'bcrypt' => [
            'rounds' => env('BCRYPT_ROUNDS', 10),
        ],
    ],
];