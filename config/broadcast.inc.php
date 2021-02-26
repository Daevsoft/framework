<?php
return [
    'connections' => [
		'driver' => 'pusher',

        'main' => [
            'auth_key' => 'your-auth-key',
            'secret'   => 'your-secret',
            'app_id'   => 'your-app-id',
            'options'  => [
				'cluster' => 'ap1',
				'encrypted' => true,
				'useTLS' => true
            ],
            'host'     => null,
            'port'     => null,
            'timeout'  => null,
        ],

        'alternative' => [
            'auth_key' => 'your-auth-key',
            'secret'   => 'your-secret',
            'app_id'   => 'your-app-id',
            'options'  => [],
            'host'     => null,
            'port'     => null,
            'timeout'  => null,
        ],

    ]
];