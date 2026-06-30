<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how each tenant's separate database is named,
    | provisioned, and connected.
    |
    */

    'db_prefix' => env('TENANT_DB_PREFIX', 'lockmytimes_tenant_'),

    'db_host' => env('TENANT_DB_HOST', '127.0.0.1'),
    'db_port' => env('TENANT_DB_PORT', '3306'),
    'db_username' => env('TENANT_DB_USERNAME', 'root'),
    'db_password' => env('TENANT_DB_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Tenant Routing
    |--------------------------------------------------------------------------
    |
    | "slug"      => lockmytimes.com/t/{slug}/...
    | "subdomain" => {slug}.lockmytimes.com/...
    |
    */

    'route_mode' => 'slug',

    'route_prefix' => 't', // used when route_mode = slug

    /*
    |--------------------------------------------------------------------------
    | Trial Period (days)
    |--------------------------------------------------------------------------
    */

    'trial_days' => env('TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Tenant Migrations Path
    |--------------------------------------------------------------------------
    */

    'migrations_path' => database_path('migrations/tenant'),

    /*
    |--------------------------------------------------------------------------
    | Default Roles Created for Each New Tenant
    |--------------------------------------------------------------------------
    */

    'default_roles' => [
        'admin'    => 'Tenant Admin',
        'hr'       => 'HR Manager',
        'manager'  => 'Department Manager',
        'employee' => 'Employee',
    ],

];