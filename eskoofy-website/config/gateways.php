<?php
declare(strict_types=1);

return [
    // Default payment gateway. `manual` works today; `paddle` will be added later.
    'default' => $_ENV['GATEWAY_DEFAULT'] ?? 'manual',
    'currency' => $_ENV['GATEWAY_CURRENCY'] ?? 'USD',
    'gateways' => [
        'manual' => [
            'driver' => 'manual',
            'name'   => 'Manual / Bank Transfer',
        ],
        'paddle' => [
            'driver' => 'paddle',
            'name'   => 'Paddle',
            'vendor_id' => $_ENV['PADDLE_VENDOR_ID'] ?? '',
            'vendor_auth_code' => $_ENV['PADDLE_VENDOR_AUTH_CODE'] ?? '',
            'test_mode' => ($_ENV['PADDLE_TEST_MODE'] ?? 'true') === 'true',
        ],
    ],
];