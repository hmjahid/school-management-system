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
        ],
        // lang packs shipped in the artifact (bn + en)
        'locales' => ['en', 'bn'],
        // exactly the BD gateways
        'gateways' => ['bkash', 'rocket', 'nagad'],
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
        // exactly the INT-standard gateways
        'gateways' => ['stripe', 'paypal', 'paddle'],
    ],
];