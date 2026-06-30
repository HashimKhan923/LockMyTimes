<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        // For tenant users (admins + employees inside a tenant)
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // For mobile API
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],

        // For YOU (the SaaS owner)
        'superadmin' => [
            'driver'   => 'session',
            'provider' => 'superadmins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        // Users live in the active tenant DB
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\Tenant\User::class),
        ],

        // SuperAdmins live in the MAIN DB
        'superadmins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Main\SuperAdmin::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
        'superadmins' => [
            'provider' => 'superadmins',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];