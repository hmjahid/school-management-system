<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Credentials and endpoint configuration for each supported gateway.
    | In production these values MUST come from environment variables or
    | a secure secrets manager — never commit real credentials to source.
    |
    */

    'gateways' => [
        'bkash' => [
            'api_username' => env('BKASH_API_USERNAME'),
            'api_password' => env('BKASH_API_PASSWORD'),
            'api_key' => env('BKASH_API_KEY'),
            'api_secret' => env('BKASH_API_SECRET'),
            'sandbox_url' => env('BKASH_SANDBOX_URL', 'https://checkout.sandbox.bka.sh'),
            'live_url' => env('BKASH_LIVE_URL', 'https://checkout.bka.sh'),
            'webhook_secret' => env('BKASH_WEBHOOK_SECRET'),
        ],

        'nagad' => [
            'api_username' => env('NAGAD_API_USERNAME'),
            'api_password' => env('NAGAD_API_PASSWORD'),
            'api_key' => env('NAGAD_API_KEY'),
            'api_secret' => env('NAGAD_API_SECRET'),
            'sandbox_url' => env('NAGAD_SANDBOX_URL', 'https://sandbox.mynagad.com'),
            'live_url' => env('NAGAD_LIVE_URL', 'https://api.mynagad.com/api'),
            'webhook_secret' => env('NAGAD_WEBHOOK_SECRET'),
        ],

        'rocket' => [
            'api_username' => env('ROCKET_API_USERNAME'),
            'api_password' => env('ROCKET_API_PASSWORD'),
            'api_key' => env('ROCKET_API_KEY'),
            'api_secret' => env('ROCKET_API_SECRET'),
            'sandbox_url' => env('ROCKET_SANDBOX_URL', 'https://api.sandbox.rocket.com.bd/api/v1'),
            'live_url' => env('ROCKET_LIVE_URL', 'https://api.rocket.com.bd/api/v1'),
            'webhook_secret' => env('ROCKET_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Offline / Bank Transfer Settings
    |--------------------------------------------------------------------------
    |
    | Displayed on receipts and the payment page when an offline gateway
    | (cash/bank transfer) is selected.
    |
    */

    'offline' => [
        'account_name' => env('OFFLINE_ACCOUNT_NAME', env('APP_NAME', 'School').' Account'),
        'account_number' => env('OFFLINE_ACCOUNT_NUMBER'),
        'bank_name' => env('OFFLINE_BANK_NAME'),
        'branch' => env('OFFLINE_BRANCH_NAME'),
        'routing_number' => env('OFFLINE_ROUTING_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refund Settings
    |--------------------------------------------------------------------------
    */

    'refund' => [
        // Whether to process refunds asynchronously via the queue.
        'queue' => env('PAYMENT_REFUND_QUEUE', false),
    ],
];
