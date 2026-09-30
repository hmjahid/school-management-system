<?php

/*
|--------------------------------------------------------------------------
| Eskoofy variant profiles (bd / int)
|--------------------------------------------------------------------------
|
| Single source of truth for what a `bd` vs `int` export changes.
| Consumed by build/export.sh. Keep every difference here as DATA —
| never scatter variant branching inside the products.
|
| bd  = current Bangladeshi behaviour (Bengali+English, ministry links,
|       bKash/Rocket/Nagad).
| int = international (English only, PayPal/Stripe/Paddle, no BD links,
|       USD, UTC).
|
*/

return [
    'bd' => [
        'label' => 'Bangladesh',
        'env' => [
            'ESKOOFY_VARIANT=bd',
            'APP_NAME="Eskoofy"',
            'APP_LOCALE=bn',
            'APP_FALLBACK_LOCALE=en',
            'PAYMENT_CURRENCY=BDT',
            'TIMEZONE=Asia/Dhaka',
            'ESKOOFY_MINISTRY_LINKS=true',
            'ESKOOFY_MINISTRY_BADGE=true',
        ],
        // lang packs shipped in the artifact (bn + en)
        'locales' => ['en', 'bn'],
        // BD gateways, plus the optional BD aggregator and the international
        // gateways (all off until an admin enables them).
        'gateways' => [
            'bkash', 'rocket', 'nagad', 'uddoktapay',
            'gpay', 'applepay', 'razorpay', 'paystack', 'flutterwave', 'sslcommerz',
            'square', 'mollie', 'authorize_net', 'xendit', 'adyen', 'skrill',
        ],
    ],

    'int' => [
        'label' => 'International',
        'env' => [
            'ESKOOFY_VARIANT=int',
            'APP_NAME="Eskoofy"',
            'APP_LOCALE=en',
            'APP_FALLBACK_LOCALE=en',
            'PAYMENT_CURRENCY=USD',
            'TIMEZONE=UTC',
            'ESKOOFY_MINISTRY_LINKS=false',
            'ESKOOFY_MINISTRY_BADGE=false',
        ],
        // only English ships; bn/others stripped at export time
        'locales' => ['en'],
        // INT-standard gateways plus the optional international gateways
        // (all off until an admin enables them).
        'gateways' => [
            'stripe', 'paypal', 'paddle',
            'gpay', 'applepay', 'razorpay', 'paystack', 'flutterwave', 'sslcommerz',
            'square', 'mollie', 'authorize_net', 'xendit', 'adyen', 'skrill',
        ],
    ],
];