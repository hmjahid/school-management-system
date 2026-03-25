<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS driver that is used to send any SMS
    | messages sent by your application. Alternative drivers may be setup
    | and used as needed; however, this default driver will be used by default.
    |
    */

    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | SMS Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the driver information for each service that
    | is used by your application. A default configuration has been added
    | for each driver as an example of the required options.
    |
    */

    'drivers' => [
        'log' => [
            'driver' => 'log',
        ],
        
        'twilio' => [
            'driver' => 'twilio',
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM_NUMBER'),
        ],
        
        'nexmo' => [
            'driver' => 'nexmo',
            'api_key' => env('NEXMO_KEY'),
            'api_secret' => env('NEXMO_SECRET'),
            'from' => env('NEXMO_FROM_NUMBER'),
        ],
        
        'textlocal' => [
            'driver' => 'textlocal',
            'api_key' => env('TEXTLOCAL_API_KEY'),
            'sender' => env('TEXTLOCAL_SENDER'),
            'test_mode' => env('TEXTLOCAL_TEST_MODE', false),
        ],
        
        'africastalking' => [
            'driver' => 'africastalking',
            'api_key' => env('AFRICASTALKING_API_KEY'),
            'username' => env('AFRICASTALKING_USERNAME'),
            'from' => env('AFRICASTALKING_FROM'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Number
    |--------------------------------------------------------------------------
    |
    | You may specify a default "from" number for all SMS messages sent by
    | the application. This number should be in a format that is accepted
    | by your chosen SMS provider.
    |
    */

    'from' => env('SMS_FROM', 'SchoolMS'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether SMS messages should be sent
    | asynchronously using the queue. If this is set to true, the SMS
    | messages will be dispatched to the default queue.
    |
    */

    'queue' => env('SMS_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify the queue connection that should be
    | used to send SMS messages. If set to null, the default queue connection
    | will be used.
    |
    */

    'queue_connection' => env('SMS_QUEUE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify the queue name that should be used
    | when sending SMS messages asynchronously.
    |
    */

    'queue_name' => env('SMS_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Callbacks
    |--------------------------------------------------------------------------
    |
    | Here you can define callback URLs for delivery reports and incoming
    | messages. These URLs will be called by the SMS provider when a
    | message status changes or when a new message is received.
    |
    */

    'callbacks' => [
        'status' => env('SMS_STATUS_CALLBACK', '/api/sms/status'),
        'incoming' => env('SMS_INCOMING_CALLBACK', '/api/sms/incoming'),
    ],
];
