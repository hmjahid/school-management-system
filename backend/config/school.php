<?php

return [

    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),

    /*
    | Full iframe embed URL from Google Maps “Share → Embed a map”, or leave null
    | to show a generic map link using the school address from settings.
    */
    'google_maps_embed_url' => env('GOOGLE_MAPS_EMBED_URL'),

    'supported_locales' => ['en', 'bn'],

];
