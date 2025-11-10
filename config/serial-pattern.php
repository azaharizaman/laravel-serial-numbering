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
        // Example: Fiscal year invoice (April to March)
        'fiscal_invoice' => [
            'pattern' => 'FY-{fiscal_year}-{number}',
            'start' => 1,
            'digits' => 5,
            'reset' => 'custom',
            'reset_strategy' => \AzahariZaman\ControlledNumber\Resets\FiscalYearReset::class,
            'reset_config' => [
                'start_month' => 4, // April
                'start_day' => 1,
            ],
            'delimiters' => ['-'],
        ],
        // Example: Business day ticket (skips weekends)
        'ticket' => [
            'pattern' => 'TKT-{year}{month}{day}-{number}',
            'start' => 1,
            'digits' => 4,
            'reset' => 'custom',
            'reset_strategy' => \AzahariZaman\ControlledNumber\Resets\BusinessDayReset::class,
            'reset_config' => [
                'skip_days' => [0, 6], // Sunday and Saturday
                'holidays' => [], // Add Y-m-d formatted dates
            ],
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
        
        // Activity log integration (requires spatie/laravel-activitylog)
        'activity_log' => [
            'enabled' => true,
            'log_name' => 'serial', // Log name for activity log
            'include_properties' => true, // Log additional context
        ],
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

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Enable RESTful API endpoints for serial number operations.
    | Requires Laravel Sanctum for authentication.
    |
    */


    'api' => [
        'enabled' => env('SERIAL_API_ENABLED', false),
        'prefix' => 'api/v1/serial-numbers',
        'middleware' => ['api', 'auth:sanctum'],
        
        // Rate limiting per pattern type
        'rate_limit' => [
            'enabled' => true,
            'max_attempts' => 60, // requests per window
            'decay_minutes' => 1, // time window in minutes
        ],
    ],
];
