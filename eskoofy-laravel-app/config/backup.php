<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    |
    | Provider key used until an install stores its own choice in the
    | `cloud_backup_settings` table. `local` never fails — it mirrors the
    | portable zip into `storage/app/backups/cloud`, which makes it the safe
    | default for shared hosting and for tests.
    |
    | Supported keys: local, google_drive, dropbox, 4shared, s3
    |
    */

    'default_provider' => env('CLOUD_BACKUP_PROVIDER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Cloud backup root
    |--------------------------------------------------------------------------
    |
    | Remote folder all backups are uploaded into (each provider prefixes it
    | with its own path semantics). The local mirror lives in
    | `storage/app/backups/cloud` and is never web-accessible.
    |
    */

    'folder' => env('CLOUD_BACKUP_FOLDER', 'eskoofy-backups'),

    /*
    |--------------------------------------------------------------------------
    | Local staging directory
    |--------------------------------------------------------------------------
    |
    | Sub-directory of the local `backups` folder that the archive is written to
    | before it is uploaded. Keeping it separate from the manually created
    | archives means the cloud run can never pick up (or prune) a file an admin
    | downloaded from the Backups page.
    |
    */

    'scratch_dir' => 'cloud-staging',

    /*
    |--------------------------------------------------------------------------
    | Automatic backup
    |--------------------------------------------------------------------------
    |
    | `dispatch_every_minutes` is the fixed cadence of the dispatcher command
    | (scheduled in routes/console.php). The dispatcher compares the install's
    | own `interval_minutes` against `last_run_at`, so the interval stays
    | user-configurable without touching cron.
    |
    | Interval floor is 5 minutes, retention floor is 1 file.
    |
    */

    'auto' => [
        'enabled' => env('CLOUD_BACKUP_AUTO', false),
        'interval_minutes' => (int) env('CLOUD_BACKUP_INTERVAL_MINUTES', 60),
        'min_interval_minutes' => 5,
        'max_interval_minutes' => 10080,
        'keep' => (int) env('CLOUD_BACKUP_KEEP', 7),
        'min_keep' => 1,
        'max_keep' => 365,
        'run_at' => env('CLOUD_BACKUP_RUN_AT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP client
    |--------------------------------------------------------------------------
    |
    | Provider calls are HTTPS-only, TLS-verified and always time-bounded.
    |
    */

    'http' => [
        'timeout' => (int) env('CLOUD_BACKUP_TIMEOUT', 60),
        'connect_timeout' => (int) env('CLOUD_BACKUP_CONNECT_TIMEOUT', 15),
        'verify_tls' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock
    |--------------------------------------------------------------------------
    |
    | Upload lock so a slow provider call never overlaps the next dispatcher
    | tick. The lock is held for `seconds`.
    |
    */

    'lock' => [
        'key' => 'eskoofy-cloud-backup',
        'seconds' => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment credential fallbacks
    |--------------------------------------------------------------------------
    |
    | Optional per-provider env fallbacks for installs that manage secrets
    | outside the database. Anything set here is merged under the stored
    | credentials (stored values win).
    |
    */

    'env' => [
        'google_drive' => [
            'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'service_account_email' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_EMAIL'),
            'service_account_private_key' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_PRIVATE_KEY'),
            'scope' => env('GOOGLE_DRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file'),
        ],
        'dropbox' => [
            'app_key' => env('DROPBOX_APP_KEY'),
            'app_secret' => env('DROPBOX_APP_SECRET'),
            'refresh_token' => env('DROPBOX_REFRESH_TOKEN'),
            'access_token' => env('DROPBOX_ACCESS_TOKEN'),
        ],
        '4shared' => [
            'api_key' => env('FOURSHARED_API_KEY'),
            'username' => env('FOURSHARED_USERNAME'),
            'password' => env('FOURSHARED_PASSWORD'),
        ],
        's3' => [
            'endpoint' => env('S3_ENDPOINT'),
            'bucket' => env('S3_BUCKET'),
            'key' => env('S3_KEY'),
            'secret' => env('S3_SECRET'),
            'region' => env('S3_REGION', 'us-east-1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider registry
    |--------------------------------------------------------------------------
    |
    | label    = human name shown in the settings form
    | fields   = credential inputs (encrypted at rest, never echoed back)
    | required = fields needed before the provider can be tested
    |
    */

    'providers' => [
        'local' => [
            'label' => 'This server (local folder)',
            'fields' => [],
            'required' => [],
        ],
        'google_drive' => [
            'label' => 'Google Drive',
            'fields' => [
                'client_id' => 'Client ID',
                'client_secret' => 'Client secret',
                'refresh_token' => 'Refresh token',
                'service_account_email' => 'Service account email',
                'service_account_private_key' => 'Service account private key',
                'scope' => 'OAuth scope (optional)',
            ],
            // Either credential set works; see CloudBackupManager::isConfigured().
            'required' => ['client_id', 'client_secret', 'refresh_token'],
            'alternatives' => [
                [
                    'label' => 'OAuth2 refresh token',
                    'fields' => ['client_id', 'client_secret', 'refresh_token'],
                ],
                [
                    'label' => 'Service account',
                    'fields' => ['service_account_email', 'service_account_private_key'],
                ],
            ],
        ],
        'dropbox' => [
            'label' => 'Dropbox',
            'fields' => [
                'app_key' => 'App key',
                'app_secret' => 'App secret',
                'refresh_token' => 'Refresh token',
                'access_token' => 'Access token (short-lived alternative)',
            ],
            'required' => ['app_key', 'app_secret'],
            'alternatives' => [
                [
                    'label' => 'App + refresh token',
                    'fields' => ['app_key', 'app_secret', 'refresh_token'],
                ],
                [
                    'label' => 'Access token',
                    'fields' => ['access_token'],
                ],
            ],
        ],
        '4shared' => [
            'label' => '4shared',
            'fields' => [
                'api_key' => 'API key',
                'username' => 'Username',
                'password' => 'Password',
            ],
            'required' => ['api_key'],
        ],
        's3' => [
            'label' => 'Amazon S3 (or compatible)',
            'fields' => [
                'endpoint' => 'Endpoint',
                'bucket' => 'Bucket',
                'key' => 'Access key',
                'secret' => 'Secret key',
                'region' => 'Region',
            ],
            'required' => ['bucket', 'key', 'secret'],
        ],
    ],

];
