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

        // International (int variant) messaging drivers. The active driver is chosen
        // by SMS_DRIVER (build profiles set SMS_DRIVER=twilio for int). The bd variant
        // keeps today's behavior with the `log` driver.
        'sms' => [
            'international_drivers' => ['twilio', 'vonage', 'nexmo'],
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

    /*
    |----------------------------------------------------------------------
    | Document designs (certificate / testimonial / marksheet / admit_card /
    | id_card) — the shipped bd-profile look. The install's active
    | `document_designs` row overrides these per type.
    |----------------------------------------------------------------------
    |
    */
    'documents' => [

        'types' => ['certificate', 'testimonial', 'marksheet', 'admit_card', 'id_card'],

        'templates' => ['classic', 'modern', 'minimal', 'bordered'],

        'watermark' => [
            'enabled' => ($_ENV['DOCUMENT_WATERMARK_ENABLED'] ?? getenv('DOCUMENT_WATERMARK_ENABLED') ?: false),
            'type' => 'text', // text | image | logo
            'text' => ($_ENV['DOCUMENT_WATERMARK_TEXT'] ?? getenv('DOCUMENT_WATERMARK_TEXT') ?: null),
            'image_path' => null,
            'opacity' => 0.18,
            'rotation' => 45,
            'position' => 'diagonal', // center | diagonal | tile | top | bottom
            'font_size' => 48,
            'color' => '#0f172a',
            'font_family' => 'inherit',
            'documents' => [
                'certificate' => true,
                'testimonial' => true,
                'marksheet' => true,
                'admit_card' => true,
                'id_card' => true,
            ],
        ],

        'defaults' => [
            'certificate' => [
                'template' => 'classic',
                'primary_color' => '#1e40af',
                'secondary_color' => '#1e3a8a',
                'accent_color' => '#2563eb',
                'muted_color' => '#64748b',
                'background_color' => '#ffffff',
                'font_family' => 'Georgia, serif',
                'base_font_size' => 16,
                'title_font_size' => 24,
                'border_style' => 'double',
                'border_width' => 3,
                'border_color' => '#2563eb',
                'border_radius' => 0,
                'page_size' => 'a4',
                'orientation' => 'landscape',
                'padding' => 48,
                'accent_bar' => 'none',
            ],
            'testimonial' => [
                'template' => 'classic',
                'primary_color' => '#16a34a',
                'secondary_color' => '#166534',
                'accent_color' => '#16a34a',
                'muted_color' => '#64748b',
                'background_color' => '#ffffff',
                'font_family' => 'Georgia, serif',
                'base_font_size' => 16,
                'title_font_size' => 24,
                'border_style' => 'double',
                'border_width' => 3,
                'border_color' => '#16a34a',
                'border_radius' => 0,
                'page_size' => 'a4',
                'orientation' => 'landscape',
                'padding' => 48,
                'accent_bar' => 'none',
            ],
            'marksheet' => [
                'template' => 'modern',
                'primary_color' => '#1f2937',
                'secondary_color' => '#4b5563',
                'accent_color' => '#2563eb',
                'muted_color' => '#6b7280',
                'background_color' => '#ffffff',
                'font_family' => 'Helvetica, Arial, sans-serif',
                'base_font_size' => 13,
                'title_font_size' => 22,
                'border_style' => 'solid',
                'border_width' => 1,
                'border_color' => '#d1d5db',
                'border_radius' => 8,
                'page_size' => 'a4',
                'orientation' => 'portrait',
                'padding' => 40,
                'accent_bar' => 'bottom',
            ],
            'admit_card' => [
                'template' => 'modern',
                'primary_color' => '#1e40af',
                'secondary_color' => '#1e3a8a',
                'accent_color' => '#1e40af',
                'muted_color' => '#6b7280',
                'background_color' => '#ffffff',
                'font_family' => 'Arial, Helvetica, sans-serif',
                'base_font_size' => 14,
                'title_font_size' => 24,
                'border_style' => 'solid',
                'border_width' => 3,
                'border_color' => '#1e40af',
                'border_radius' => 12,
                'page_size' => 'a4',
                'orientation' => 'portrait',
                'padding' => 30,
                'accent_bar' => 'top',
            ],
            'id_card' => [
                'template' => 'modern',
                'primary_color' => '#1e40af',
                'secondary_color' => '#1e3a8a',
                'accent_color' => '#1e40af',
                'muted_color' => '#6b7280',
                'background_color' => '#ffffff',
                'font_family' => 'Arial, Helvetica, sans-serif',
                'base_font_size' => 13,
                'title_font_size' => 18,
                'border_style' => 'solid',
                'border_width' => 3,
                'border_color' => '#1e40af',
                'border_radius' => 12,
                'page_size' => 'credit-card',
                'orientation' => 'landscape',
                'padding' => 20,
                'accent_bar' => 'none',
            ],
        ],

    ],

    /*
    |----------------------------------------------------------------------
    | Cross-variant restore reconciliation
    |----------------------------------------------------------------------
    |
    | SQL backups carry a `-- eskoofy-variant:` header. When a backup taken in
    | one variant (bd/int) is restored into another, variant-owned
    | configuration rows are re-set to the RECEIVING variant's defaults;
    | business data (students, staff, fees, results, ...) is restored verbatim.
    |
    */
    'restore' => [
        // Gateways eligible per variant — anything else is deactivated after a
        // cross-variant restore (never deleted, so custom gateway rows survive).
        'gateways' => [
            'bd' => ['bkash', 'rocket', 'nagad', 'cash', 'bank_transfer', 'cheque'],
            'int' => ['stripe', 'paypal', 'paddle', 'cash', 'bank_transfer', 'cheque'],
        ],
        // Variant-owned settings forced onto the receiving variant's row(s).
        'settings' => [
            'currency' => ['bd' => 'BDT', 'int' => 'USD'],
            'default_payment_method' => ['bd' => 'bkash', 'int' => 'stripe'],
            'default_locale' => ['bd' => 'en', 'int' => 'en'],
        ],
        // When restoring into int: drop Bengali UI columns (`*_bn`) and the BD
        // admission payment number so no remnant of the bd profile surfaces.
        'strip_bangla_for_int' => true,
    ],
];
