<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Public Registration
    |--------------------------------------------------------------------------
    |
    | Roles that may be self-assigned during public API registration.
    | Admin, teacher, and accountant roles must be assigned by an admin.
    |
    */

    'self_register_roles' => ['student', 'parent'],

    /*
    |--------------------------------------------------------------------------
    | CORS Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Comma-separated list in .env as CORS_ALLOWED_ORIGINS.
    | Falls back to APP_URL when not set.
    |
    */

    'cors_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost')))
    )),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'auth' => env('API_RATE_LIMIT_AUTH', 10),
        'public' => env('API_RATE_LIMIT_PUBLIC', 60),
        'authenticated' => env('API_RATE_LIMIT_AUTHENTICATED', 120),
    ],

];
