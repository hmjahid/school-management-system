<?php
declare(strict_types=1);

return [
    'name'    => $_ENV['APP_NAME'] ?? 'Eskoofy',
    'url'     => $_ENV['APP_URL'] ?? 'http://localhost:8001',
    'locale'  => $_ENV['APP_LOCALE'] ?? 'en',
    'variant' => $_ENV['ESKOOFY_VARIANT'] ?? 'int',
    'debug'   => ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'site'    => [
        'name'      => $_ENV['SITE_NAME'] ?? 'Eskoofy',
        'tagline'   => $_ENV['SITE_TAGLINE'] ?? 'School management software & WordPress theme',
    ],
    'i18n' => [
        // The website is ONE codebase with a language switcher. Default en;
        // more locales light up once their lang/{code}.php ship.
        'default' => $_ENV['APP_LOCALE'] ?? 'en',
        'locales' => [
            'en' => 'English',
            'bn' => 'বাংলা',
        ],
        // Location-based default language: Bangladeshi visitors get `bd_locale`,
        // everyone else gets `other_locale`. A manual switch always wins.
        'geo' => [
            'enabled'            => $_ENV['GEO_LANG_ENABLED'] ?? 'true',
            'cdn_headers'        => ['CF-IPCountry', 'X-IPCountry', 'IPCountry'],
            'accept_language'    => true,
            'remote_api_url'     => $_ENV['GEO_IP_API_URL'] ?? '',
            'remote_api_timeout' => 2,
            'use_timezone_hint'  => true,
            'bd_locale'          => 'bn',
            'other_locale'       => 'en',
        ],
    ],
];