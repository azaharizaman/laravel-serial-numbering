<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Serial Patterns Configuration
    |--------------------------------------------------------------------------
    |
    | Define your serial number patterns here. Each pattern supports dynamic
    | segments like {year}, {month}, {number}, and custom model properties.
    |
    */

    'patterns' => [
        'invoice' => [
            'pattern' => 'INV-{year}-{month}-{number}',
            'start' => 1000,
            'digits' => 5,
            'reset' => 'monthly',
            'interval' => 1,
            'delimiters' => ['-', '/'],
        ],
        'order' => [
            'pattern' => 'ORD-{year}{month}{number}',
            'start' => 1,
            'digits' => 6,
            'reset' => 'daily',
            'interval' => 1,
            'delimiters' => ['-'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable comprehensive audit logging for all serial number operations.
    | Track which user generated each serial and when.
    |
    */

    'logging' => [
        'enabled' => true,
        'track_user' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Segment Resolvers
    |--------------------------------------------------------------------------
    |
    | Register custom segment resolvers for specialized pattern segments.
    | Format: 'segment_name' => ResolverClass::class
    |
    */

    'segments' => [
        // 'custom.code' => \App\Segments\CustomCodeResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrency Settings
    |--------------------------------------------------------------------------
    |
    | Configure atomic locking to prevent race conditions during serial
    | generation in high-concurrency environments.
    |
    */

    'lock' => [
        'enabled' => true,
        'timeout' => 10, // seconds
        'store' => 'default', // cache store to use for locks
    ],
];
