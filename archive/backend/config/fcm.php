<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Config
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Firebase Cloud Messaging (FCM).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file or array of credentials.
    | You can get this file from the Firebase Console under Project Settings.
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        
        // Alternatively, you can provide the credentials directly as an array
        'json' => [
            'type' => 'service_account',
            'project_id' => env('FIREBASE_PROJECT_ID'),
            'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
            'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY', '')),
            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
            'client_id' => env('FIREBASE_CLIENT_ID'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID. This is required for some operations.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Default Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for push notifications.
    |
    */

    'messaging' => [
        // Default notification icon for Android
        'android_icon' => 'notification_icon',
        
        // Default notification sound for Android
        'android_sound' => 'default',
        
        // Default notification color for Android (in #RRGGBB format)
        'android_color' => '#4a86e8',
        
        // Default notification priority (normal or high)
        'android_priority' => 'high',
        
        // Default notification sound for iOS
        'ios_sound' => 'default',
        
        // Default badge count for iOS
        'ios_badge' => 1,
        
        // Default content available flag for iOS background notifications
        'content_available' => true,
        
        // Default mutable content flag for iOS notification extensions
        'mutable_content' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the HTTP client used to send requests to FCM.
    |
    */

    'http' => [
        // Timeout in seconds for the HTTP client
        'timeout' => 30,
        
        // Number of times to retry failed requests
        'retry_attempts' => 3,
        
        // Delay between retries in seconds
        'retry_delay' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging FCM requests and responses.
    |
    */

    'logging' => [
        // Whether to log FCM requests and responses
        'enabled' => env('FCM_LOGGING', env('APP_DEBUG', false)),
        
        // Log channel to use
        'channel' => env('FCM_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for caching FCM tokens and other data.
    |
    */

    'cache' => [
        // Whether to enable caching
        'enabled' => env('FCM_CACHE_ENABLED', true),
        
        // Cache store to use
        'store' => env('FCM_CACHE_STORE', 'file'),
        
        // Cache prefix
        'prefix' => 'fcm',
        
        // Cache TTL in minutes
        'ttl' => 60 * 24, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Topics
    |--------------------------------------------------------------------------
    |
    | Configuration for FCM topics.
    |
    */

    'topics' => [
        // Topic name prefix
        'prefix' => env('FCM_TOPIC_PREFIX', ''),
        
        // Maximum number of devices to subscribe/unsubscribe in a single batch
        'batch_size' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Payload
    |--------------------------------------------------------------------------
    |
    | Default structure for notification payloads.
    |
    */

    'default_notification' => [
        'title' => 'New Notification',
        'body' => 'You have a new notification',
        'sound' => 'default',
        'badge' => 1,
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'priority' => 'high',
    ],
];
