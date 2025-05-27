<?php

return [
    'db' => [
        'dsn' => function () { return [
            'hostspec' => constant('App\DB_HOST'),
            'username' => constant('App\DB_USER'),
            'password' => constant('App\DB_PASS'),
            'database' => constant('App\DB_NAME'),
        ];},
        'storePasswords' => true,
    ],
    'cloveDb' => function () { return [
        'hostspec' => constant('Clove\DB_HOST'),
        'username' => constant('Clove\DB_USER'),
        'password' => constant('Clove\DB_PASS'),
        'database' => constant('Clove\DB_NAME'),
    ];},
    'module'  => require(__DIR__ . '/kickstart/module.vagrant.root.php'),
    'time' => [
        'server' => [
            '0.north-america.pool.ntp.org',
            '1.north-america.pool.ntp.org',
            '2.north-america.pool.ntp.org',
            '3.north-america.pool.ntp.org',
        ]
    ]
];
