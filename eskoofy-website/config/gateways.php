<?php
declare(strict_types=1);

return [
    // Default payment gateway. `manual` works today; BD/INT online gateways
    // activate once credentials are set in .env (sandbox first).
    'default' => $_ENV['GATEWAY_DEFAULT'] ?? 'manual',
    'currency' => $_ENV['GATEWAY_CURRENCY'] ?? 'USD',
    // USD → BDT rate for deriving BDT prices at checkout for BD customers.
    'bdt_rate' => (float) ($_ENV['GATEWAY_BDT_RATE'] ?? 110),
    'gateways' => [
        'manual' => [
            'driver' => 'manual',
            'name'   => 'Manual / Bank Transfer',
        ],
        // ── BD local gateways ───────────────────────────────────────────
        'bkash' => [
            'driver'     => 'bkash',
            'name'       => 'bKash',
            'test_mode'  => ($_ENV['BKASH_TEST_MODE'] ?? 'true') === 'true',
            'app_key'    => $_ENV['BKASH_APP_KEY'] ?? '',
            'app_secret' => $_ENV['BKASH_APP_SECRET'] ?? '',
            'username'   => $_ENV['BKASH_USERNAME'] ?? '',
            'password'   => $_ENV['BKASH_PASSWORD'] ?? '',
            'live_url'   => $_ENV['BKASH_LIVE_URL'] ?? '',
        ],
        'rocket' => [
            'driver'     => 'rocket',
            'name'       => 'Rocket',
            'test_mode'  => ($_ENV['ROCKET_TEST_MODE'] ?? 'true') === 'true',
            'username'   => $_ENV['ROCKET_API_USERNAME'] ?? '',
            'password'   => $_ENV['ROCKET_API_PASSWORD'] ?? '',
            'api_key'    => $_ENV['ROCKET_API_KEY'] ?? '',
        ],
        'nagad' => [
            'driver'     => 'nagad',
            'name'       => 'Nagad',
            'test_mode'  => ($_ENV['NAGAD_TEST_MODE'] ?? 'true') === 'true',
            'merchant_id'=> $_ENV['NAGAD_MERCHANT_ID'] ?? '',
            'api_key'    => $_ENV['NAGAD_API_KEY'] ?? '',
            'api_secret' => $_ENV['NAGAD_API_SECRET'] ?? '',
        ],
        // ── International gateways ──────────────────────────────────────
        'stripe' => [
            'driver'          => 'stripe',
            'name'            => 'Stripe',
            'test_mode'       => ($_ENV['STRIPE_TEST_MODE'] ?? 'true') === 'true',
            'secret_key'      => $_ENV['STRIPE_SECRET_KEY'] ?? '',
            'publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
            'webhook_secret'  => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '',
        ],
        'paypal' => [
            'driver'          => 'paypal',
            'name'            => 'PayPal',
            'test_mode'       => ($_ENV['PAYPAL_TEST_MODE'] ?? 'true') === 'true',
            'client_id'       => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
            'client_secret'   => $_ENV['PAYPAL_CLIENT_SECRET'] ?? '',
            'webhook_id'      => $_ENV['PAYPAL_WEBHOOK_ID'] ?? '',
        ],
        'paddle' => [
            'driver'          => 'paddle',
            'name'            => 'Paddle',
            'vendor_id'       => $_ENV['PADDLE_VENDOR_ID'] ?? '',
            'vendor_auth_code'=> $_ENV['PADDLE_VENDOR_AUTH_CODE'] ?? '',
            'webhook_secret'  => $_ENV['PADDLE_WEBHOOK_SECRET'] ?? '',
            'test_mode'       => ($_ENV['PADDLE_TEST_MODE'] ?? 'true') === 'true',
        ],
    ],
];