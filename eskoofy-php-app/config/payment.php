<?php
declare(strict_types=1);

return [
    'default' => $_ENV['PAYMENT_DEFAULT_GATEWAY'] ?? 'bkash',

    'currency' => $_ENV['PAYMENT_CURRENCY'] ?? 'BDT',

    'gateways' => [
        'bkash' => [
            'name'     => 'bKash',
            'type'     => 'mobile_financial_service',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['BKASH_TEST_MODE'] ?? 'true') === 'true',
            'sandbox_url' => $_ENV['BKASH_SANDBOX_URL'] ?? '',
            'live_url'    => $_ENV['BKASH_LIVE_URL'] ?? '',
            'api_key'     => $_ENV['BKASH_API_KEY'] ?? '',
            'api_secret'  => $_ENV['BKASH_API_SECRET'] ?? '',
        ],
        'rocket' => [
            'name'     => 'Rocket',
            'type'     => 'mobile_financial_service',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['ROCKET_TEST_MODE'] ?? 'true') === 'true',
            'sandbox_url' => $_ENV['ROCKET_SANDBOX_URL'] ?? '',
            'live_url'    => $_ENV['ROCKET_LIVE_URL'] ?? '',
            'api_key'     => $_ENV['ROCKET_API_KEY'] ?? '',
            'api_secret'  => $_ENV['ROCKET_API_SECRET'] ?? '',
        ],
        'nagad' => [
            'name'     => 'Nagad',
            'type'     => 'mobile_financial_service',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['NAGAD_TEST_MODE'] ?? 'true') === 'true',
            'sandbox_url' => $_ENV['NAGAD_SANDBOX_URL'] ?? '',
            'live_url'    => $_ENV['NAGAD_LIVE_URL'] ?? '',
            'api_key'     => $_ENV['NAGAD_API_KEY'] ?? '',
            'api_secret'  => $_ENV['NAGAD_API_SECRET'] ?? '',
        ],
        'stripe' => [
            'name'     => 'Stripe',
            'type'     => 'online_payment',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['STRIPE_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['STRIPE_SECRET_KEY'] ?? '',
            'publishable' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
            'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '',
        ],
        'paypal' => [
            'name'     => 'PayPal',
            'type'     => 'online_payment',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['PAYPAL_TEST_MODE'] ?? 'true') === 'true',
            'client_id'     => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? '',
            'mode'          => $_ENV['PAYPAL_MODE'] ?? 'sandbox',
        ],
        'paddle' => [
            'name'     => 'Paddle',
            'type'     => 'online_payment',
            'is_online' => true,
            'has_api'   => true,
            'test_mode' => ($_ENV['PADDLE_TEST_MODE'] ?? 'true') === 'true',
            'vendor_id' => $_ENV['PADDLE_VENDOR_ID'] ?? '',
            'vendor_auth_code' => $_ENV['PADDLE_VENDOR_AUTH_CODE'] ?? '',
        ],
    ],

    'offline' => [
        'bank_name'   => $_ENV['OFFLINE_BANK_NAME'] ?? '',
        'account_name' => $_ENV['OFFLINE_ACCOUNT_NAME'] ?? '',
        'account_number' => $_ENV['OFFLINE_ACCOUNT_NUMBER'] ?? '',
        'routing_number' => $_ENV['OFFLINE_ROUTING_NUMBER'] ?? '',
        'instructions'   => $_ENV['OFFLINE_INSTRUCTIONS'] ?? '',
    ],
];
