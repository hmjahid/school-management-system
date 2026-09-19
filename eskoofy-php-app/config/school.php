<?php

return [

    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),

    /*
    | Full iframe embed URL from Google Maps “Share → Embed a map”, or leave null
    | to show a generic map link using the school address from settings.
    */
    'google_maps_embed_url' => env('GOOGLE_MAPS_EMBED_URL'),

    'supported_locales' => ['en', 'bn'],

    /*
    | Shown in the public site top bar and footer when website settings are empty.
    | Prefer filling Dashboard → Settings; these are optional env fallbacks.
    */
    'contact_phone' => env('SCHOOL_CONTACT_PHONE'),
    'contact_email' => env('SCHOOL_CONTACT_EMAIL'),
    'contact_address' => env('SCHOOL_CONTACT_ADDRESS'),

    /*
    | Shown on the public site when no real value is configured (prompts admins to fill settings).
    */
    'placeholder_phone' => env('SCHOOL_PLACEHOLDER_PHONE', '+1 (555) 000-0000'),
    'placeholder_email' => env('SCHOOL_PLACEHOLDER_EMAIL', 'office@school.example'),
    'placeholder_address' => env('SCHOOL_PLACEHOLDER_ADDRESS', '123 School Road, City, Country'),

    /*
    | Links shown in the footer "Important links" section. Each item has a
    | `key` (matches a `site_ui('footer.link_ministry_<key>')` translation
    | string) and a `url` (the external government site). Edit URLs here
    | to point to your country's equivalents; the labels are localized via
    | lang/{locale}/site_frontend.php under the `footer` key.
    */
    'important_links' => [
        ['key' => 'education_ministry',  'url' => 'https://moedu.gov.bd/'],
        ['key' => 'primary_education',   'url' => 'https://dpe.gov.bd/'],
        ['key' => 'national_info_center', 'url' => 'https://bcc.gov.bd/'],
        ['key' => 'secondary_higher_secondary', 'url' => 'https://bdshse.gov.bd/'],
    ],

];
