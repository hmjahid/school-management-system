<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Variant
    |--------------------------------------------------------------------------
    |
    | bd  = Bangladeshi version (Bengali + English, ministry links, bKash/Rocket/Nagad).
    | int = International version (English only, PayPal/Stripe/Paddle, no BD links).
    |
    | This is the SINGLE source of truth for all BD/INT differences. Variants are
    | build-time profiles (see ../../build/profiles) that override these values at
    | export time — never hardcode `variant === 'bd'` branching across the app.
    |
    */
    'variant' => env('ESKOOFY_VARIANT', 'bd'),

    /*
    |----------------------------------------------------------------------
    | Feature flags
    |----------------------------------------------------------------------
    |
    | Each flag switches a feature or content block on/off per variant.
    |
    */
    'features' => [

        'bilingual' => true,

        'homepage' => [
            // BD homepage/footer ministry & government links (moedu.gov.bd etc.)
            'ministry_links' => env('ESKOOFY_MINISTRY_LINKS', true),
            // BD "associated with" / ministry hero badge block, if present
            'ministry_badge' => env('ESKOOFY_MINISTRY_BADGE', true),
        ],

        'payments' => [
            'bkash' => true,
            'rocket' => true,
            'nagad' => true,
            'stripe' => false,
            'paypal' => false,
            'paddle' => false,
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Branding
    |----------------------------------------------------------------------
    |
    | Variant-shapeable branding defaults; overridable via site settings.
    |
    */
    'branding' => [
        'country' => 'bd', // bd | int
        'currency' => 'BDT', // BDT (bd) | USD (int)
        'contact' => 'bd', // bd | int
    ],

    /*
    |----------------------------------------------------------------------
    | INT default currency / locale
    |----------------------------------------------------------------------
    |
    */
    'int' => [
        'locale' => 'en',
        'currency' => 'USD',
        'timezone' => 'UTC',
    ],
];
