<?php

return [
    'default' => env('REDIS_DEFAULT', 'default'),
    'connections' => [
        env('REDIS_DEFAULT', 'default') => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => envInt('REDIS_PORT', 6379),
            'timeout' => envDouble('REDIS_TIMEOUT', 1),
            'pool_size' => envInt('REDIS_POOL_SIZE', 5),
            'passwd' => env('REDIS_PASSWD', null),
            'db' => envInt('REDIS_DB', 0),
            'prefix' => env('REDIS_PREFIX', 'sw-fw-less:'),
        ],
        'zipkin' => [
            'host' => env('REDIS_ZIPKIN_HOST', '127.0.0.1'),
            'port' => envInt('REDIS_ZIPKIN_PORT', 6379),
            'timeout' => envDouble('REDIS_ZIPKIN_TIMEOUT', 1),
            'pool_size' => envInt('REDIS_ZIPKIN_POOL_SIZE', 5),
            'passwd' => env('REDIS_ZIPKIN_PASSWD', null),
            'db' => envInt('REDIS_ZIPKIN_DB', 1),
            'prefix' => env('REDIS_ZIPKIN_PREFIX', 'sw-fw-less:'),
        ],
        'red_lock' => [
            'host' => env('REDIS_RED_LOCK_HOST', '127.0.0.1'),
            'port' => envInt('REDIS_RED_LOCK_PORT', 6379),
            'timeout' => envDouble('REDIS_RED_LOCK_TIMEOUT', 1),
            'pool_size' => envInt('REDIS_RED_LOCK_POOL_SIZE', 5),
            'passwd' => env('REDIS_RED_LOCK_PASSWD', null),
            'db' => envInt('REDIS_RED_LOCK_DB', 2),
            'prefix' => env('REDIS_RED_LOCK_PREFIX', 'sw-fw-less:'),
        ],
        'rate_limit' => [
            'host' => env('REDIS_RATE_LIMIT_HOST', '127.0.0.1'),
            'port' => envInt('REDIS_RATE_LIMIT_PORT', 6379),
            'timeout' => envDouble('REDIS_RATE_LIMIT_TIMEOUT', 1),
            'pool_size' => envInt('REDIS_RATE_LIMIT_POOL_SIZE', 5),
            'passwd' => env('REDIS_RATE_LIMIT_PASSWD', null),
            'db' => envInt('REDIS_RATE_LIMIT_DB', 3),
            'prefix' => env('REDIS_RATE_LIMIT_PREFIX', 'sw-fw-less:'),
        ],
        'cache' => [
            'host' => env('REDIS_CACHE_HOST', '127.0.0.1'),
            'port' => envInt('REDIS_CACHE_PORT', 6379),
            'timeout' => envDouble('REDIS_CACHE_TIMEOUT', 1),
            'pool_size' => envInt('REDIS_CACHE_POOL_SIZE', 5),
            'passwd' => env('REDIS_CACHE_PASSWD', null),
            'db' => envInt('REDIS_CACHE_DB', 4),
            'prefix' => env('REDIS_CACHE_PREFIX', 'sw-fw-less:'),
        ],
    ],
    'switch' => envInt('REDIS_SWITCH', 0),
    'pool_change_event' => envInt('REDIS_POOL_CHANGE_EVENT', 0),
    'report_pool_change' => envInt('REDIS_REPORT_POOL_CHANGE', 0),
];
