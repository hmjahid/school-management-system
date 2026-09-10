<?php
declare(strict_types=1);

return [
    /*
    | Default SMS driver. bd keeps `log` (today's behavior); int sets SMS_DRIVER=twilio
    | or SMS_DRIVER=vonage via the build profile / .env — never hardcode variant
    | branching.
    */
    'default' => $_ENV['SMS_DRIVER'] ?? 'log',

    'currency' => $_ENV['SMS_CURRENCY'] ?? 'USD',

    'aliases' => [
        'nexmo' => 'vonage',
    ],

    'drivers' => [
        'log' => [
            'driver'       => 'log',
            'log_file'     => $_ENV['SMS_LOG_FILE'] ?? null,
        ],

        'twilio' => [
            'driver'       => 'twilio',
            'account_sid'  => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
            'auth_token'   => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
            'from'         => $_ENV['TWILIO_FROM_NUMBER'] ?? '',
        ],

        'vonage' => [
            'driver'       => 'vonage',
            'api_key'      => $_ENV['VONAGE_API_KEY'] ?? ($_ENV['NEXMO_KEY'] ?? ''),
            'api_secret'   => $_ENV['VONAGE_API_SECRET'] ?? ($_ENV['NEXMO_SECRET'] ?? ''),
            'from'         => $_ENV['VONAGE_FROM'] ?? ($_ENV['NEXMO_FROM_NUMBER'] ?? 'Eskoofy'),
        ],

        'nexmo' => [
            'driver'       => 'nexmo',
            'api_key'      => $_ENV['NEXMO_KEY'] ?? '',
            'api_secret'   => $_ENV['NEXMO_SECRET'] ?? '',
            'from'         => $_ENV['NEXMO_FROM_NUMBER'] ?? '',
        ],
    ],
];