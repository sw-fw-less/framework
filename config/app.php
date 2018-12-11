<?php

return [
    //Router
    'router' => [
        ['GET', '/redis', [\App\services\DemoService::class, 'redis']],
        ['GET', '/mysql', [\App\services\DemoService::class, 'mysql']],
        ['GET', '/http', [\App\services\DemoService::class, 'http']],
    ],

    //Server
    'server' => [
        'host' => \App\components\Helper::env('SERVER_HOST', '127.0.0.1'),
        'port' => \App\components\Helper::env('SERVER_PORT', 9501),
        'reactor_num' => \App\components\Helper::env('SERVER_REACTOR_NUM', 8),
        'worker_num' => \App\components\Helper::env('SERVER_WORKER_NUM', 32),
        'daemonize' => \App\components\Helper::env('SERVER_DAEMONIZE', false),
        'backlog' => \App\components\Helper::env('SERVER_BACKLOG', 128),
        'max_request' => \App\components\Helper::env('SERVER_MAX_REQUEST', 0),
    ],

    //Redis
    'redis' => [
        'host' => \App\components\Helper::env('REDIS_HOST', '127.0.0.1'),
        'port' => \App\components\Helper::env('REDIS_PORT', 6379),
        'timeout' => \App\components\Helper::env('REDIS_TIMEOUT', 1),
        'pool_size' => \App\components\Helper::env('REDIS_POOL_SIZE', 5),
        'passwd' => \App\components\Helper::env('REDIS_PASSWD', null),
        'db' => \App\components\Helper::env('REDIS_DB', 0),
    ],

    //MySQL
    'mysql' => [
        'dsn' => \App\components\Helper::env('MYSQL_DSN', 'mysql:dbname=sw_test;host=127.0.0.1'),
        'username' => \App\components\Helper::env('MYSQL_USERNAME', 'root'),
        'passwd' => \App\components\Helper::env('MYSQL_PASSWD', null),
        'options' => [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
        'pool_size' => \App\components\Helper::env('MYSQL_POOL_SIZE', 5),
    ],
];