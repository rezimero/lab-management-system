<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'landlord'),
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Landlord guard - for superadmins and system management
        'landlord' => [
            'driver' => 'sanctum',
            'provider' => 'landlord_users',
        ],

        // Tenant guard - for lab staff, doctors, patients
        'tenant' => [
            'driver' => 'sanctum',
            'provider' => 'tenant_users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // Default users provider (for web)
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // Landlord users provider
        'landlord_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\Landlord\User::class,
        ],

        // Tenant users provider
        'tenant_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\Tenant\User::class,
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
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => 10800,
];