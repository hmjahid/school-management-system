<?php
declare(strict_types=1);

return [
    'supported_locales' => ['en', 'bn'],
    'default_locale'    => $_ENV['APP_LOCALE'] ?? 'en',
    'timezone'          => $_ENV['APP_TIMEZONE'] ?? 'Asia/Dhaka',
    'date_format'       => 'Y-m-d',
    'time_format'       => 'H:i',
    'datetime_format'   => 'Y-m-d H:i:s',
];
