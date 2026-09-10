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
    ],
];