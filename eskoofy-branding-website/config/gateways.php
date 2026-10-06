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
        'uddoktapay' => [
            'driver'      => 'uddoktapay',
            'name'        => 'UddoktaPay',
            'test_mode'   => ($_ENV['UDDOKTAPAY_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['UDDOKTAPAY_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['UDDOKTAPAY_SANDBOX_URL'] ?? 'https://sandbox.uddoktapay.com/api',
            'live_url'    => $_ENV['UDDOKTAPAY_LIVE_URL'] ?? '',
        ],
        // Extended BD hosted gateway set — config-driven, inactive until an admin
        // supplies credentials. Sandbox/live URLs are DRAFT placeholders; verify
        // against each vendor's official docs before enabling.
        'shurjopay' => [
            'driver'      => 'generic_hosted',
            'name'        => 'ShurjoPay',
            'test_mode'   => ($_ENV['SHURJOPAY_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['SHURJOPAY_API_KEY'] ?? '',
            'merchant_id' => $_ENV['SHURJOPAY_MERCHANT_ID'] ?? '',
            'sandbox_url' => $_ENV['SHURJOPAY_SANDBOX_URL'] ?? 'https://sandbox.shurjopay.io/',
            'live_url'    => $_ENV['SHURJOPAY_LIVE_URL'] ?? '',
        ],
        'portwallet' => [
            'driver'      => 'generic_hosted',
            'name'        => 'PortWallet',
            'test_mode'   => ($_ENV['PORTWALLET_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['PORTWALLET_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['PORTWALLET_SANDBOX_URL'] ?? 'https://sandbox.portwallet.com/cloud-payment/',
            'live_url'    => $_ENV['PORTWALLET_LIVE_URL'] ?? '',
        ],
        'cellfin' => [
            'driver'      => 'generic_hosted',
            'name'        => 'Cellfin',
            'test_mode'   => ($_ENV['CELLFIN_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['CELLFIN_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['CELLFIN_SANDBOX_URL'] ?? 'https://sandbox.cellfin.io',
            'live_url'    => $_ENV['CELLFIN_LIVE_URL'] ?? '',
        ],
        'purse' => [
            'driver'      => 'generic_hosted',
            'name'        => 'Purse',
            'test_mode'   => ($_ENV['PURSE_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['PURSE_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['PURSE_SANDBOX_URL'] ?? 'https://sandbox.purse.com.bd',
            'live_url'    => $_ENV['PURSE_LIVE_URL'] ?? '',
        ],
        'cashby' => [
            'driver'      => 'generic_hosted',
            'name'        => 'Cashby',
            'test_mode'   => ($_ENV['CASHBY_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['CASHBY_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['CASHBY_SANDBOX_URL'] ?? 'https://sandbox.cashby.com.bd',
            'live_url'    => $_ENV['CASHBY_LIVE_URL'] ?? '',
        ],
        'upay' => [
            'driver'      => 'generic_hosted',
            'name'        => 'UPay',
            'test_mode'   => ($_ENV['UPAY_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['UPAY_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['UPAY_SANDBOX_URL'] ?? 'https://sandbox.upay.ltd',
            'live_url'    => $_ENV['UPAY_LIVE_URL'] ?? '',
        ],
        'mycash' => [
            'driver'      => 'generic_hosted',
            'name'        => 'MyCash',
            'test_mode'   => ($_ENV['MYCASH_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['MYCASH_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['MYCASH_SANDBOX_URL'] ?? 'https://sandbox.mycash.com.bd',
            'live_url'    => $_ENV['MYCASH_LIVE_URL'] ?? '',
        ],
        'payer' => [
            'driver'      => 'generic_hosted',
            'name'        => 'Payer',
            'test_mode'   => ($_ENV['PAYER_TEST_MODE'] ?? 'true') === 'true',
            'api_key'     => $_ENV['PAYER_API_KEY'] ?? '',
            'sandbox_url' => $_ENV['PAYER_SANDBOX_URL'] ?? 'https://sandbox.payer.com.bd',
            'live_url'    => $_ENV['PAYER_LIVE_URL'] ?? '',
        ],
        // Zero-credential development gateway. Enable with TEST_GATEWAY_ENABLED=true.
        'test_gateway' => [
            'driver' => 'test_gateway',
            'name'   => 'Test / Sandbox',
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