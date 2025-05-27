<?php

return [
    'db' => [
        'dsn' => function () { return [
            'hostspec' => getenv('App_DB_HOST'),
            'username' => getenv('App_DB_USER'),
            'password' => getenv('App_DB_PASS'),
            'database' => getenv('App_DB_NAME'),
        ];},
        'storePasswords' => true,
    ],
    'cloveDb' => function () { return [
        'hostspec' => getenv('Clove_DB_HOST'),
        'username' => getenv('Clove_DB_USER'),
        'password' => getenv('Clove_DB_PASS'),
        'database' => getenv('Clove_DB_NAME'),
    ];},
    'module'  => require(__DIR__ . '/kickstart/module.docker.root.php'),
    'time' => [
        'server' => [
            '0.north-america.pool.ntp.org',
            '1.north-america.pool.ntp.org',
            '2.north-america.pool.ntp.org',
            '3.north-america.pool.ntp.org',
        ]
    ]
];
