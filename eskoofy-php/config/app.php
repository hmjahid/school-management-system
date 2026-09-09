<?php
declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME'] ?? 'Eskoofy',
    'url'      => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'locale'   => $_ENV['APP_LOCALE'] ?? 'en',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka',
    'debug'    => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'variant'  => $_ENV['ESKOOFY_VARIANT'] ?? 'bd',

    'school' => [
        'name'     => $_ENV['SCHOOL_NAME'] ?? 'Eskoofy School',
        'tagline'  => $_ENV['SCHOOL_TAGLINE'] ?? 'Excellence in Education',
        'address'  => $_ENV['SCHOOL_ADDRESS'] ?? '',
        'phone'    => $_ENV['SCHOOL_PHONE'] ?? '',
        'email'    => $_ENV['SCHOOL_EMAIL'] ?? '',
        'website'  => $_ENV['SCHOOL_WEBSITE'] ?? '',
    ],

    'features' => [
        'homepage' => [
            'ministry_links' => ($_ENV['ESKOOFY_MINISTRY_LINKS'] ?? 'true') === 'true',
            'ministry_badge' => ($_ENV['ESKOOFY_MINISTRY_BADGE'] ?? 'true') === 'true',
        ],
    ],

    'pagination' => [
        'per_page' => 15,
    ],
];
