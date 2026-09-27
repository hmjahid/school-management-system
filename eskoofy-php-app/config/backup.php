<?php

declare(strict_types=1);

/**
 * Cloud backup configuration (parity with eskoofy-laravel-app/config/backup.php).
 *
 * The raw-PHP port has no Laravel `env()` helper — values are read from the
 * environment directly (getenv / $_ENV) with the same defaults, so a shared
 * `.env` across products behaves identically.
 */

$env = static fn (string $key, mixed $default = null): mixed => $_ENV[$key] ?? getenv($key) ?: $default;

return [

    'default_provider' => $env('CLOUD_BACKUP_PROVIDER', 'local'),

    'folder' => $env('CLOUD_BACKUP_FOLDER', 'eskoofy-backups'),

    'scratch_dir' => 'cloud-staging',

    'auto' => [
        'enabled' => (bool) $env('CLOUD_BACKUP_AUTO', false),
        'interval_minutes' => (int) $env('CLOUD_BACKUP_INTERVAL_MINUTES', 60),
        'min_interval_minutes' => 5,
        'max_interval_minutes' => 10080,
        'keep' => (int) $env('CLOUD_BACKUP_KEEP', 7),
        'min_keep' => 1,
        'max_keep' => 365,
        'run_at' => $env('CLOUD_BACKUP_RUN_AT'),
    ],

    'http' => [
        'timeout' => (int) $env('CLOUD_BACKUP_TIMEOUT', 60),
        'connect_timeout' => (int) $env('CLOUD_BACKUP_CONNECT_TIMEOUT', 15),
        'verify_tls' => true,
    ],

    'lock' => [
        'key' => 'eskoofy-cloud-backup',
        'seconds' => 600,
    ],

    'env' => [
        'google_drive' => [
            'client_id' => $env('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => $env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refresh_token' => $env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'service_account_email' => $env('GOOGLE_DRIVE_SERVICE_ACCOUNT_EMAIL'),
            'service_account_private_key' => $env('GOOGLE_DRIVE_SERVICE_ACCOUNT_PRIVATE_KEY'),
            'scope' => $env('GOOGLE_DRIVE_SCOPE', 'https://www.googleapis.com/auth/drive.file'),
        ],
        'dropbox' => [
            'app_key' => $env('DROPBOX_APP_KEY'),
            'app_secret' => $env('DROPBOX_APP_SECRET'),
            'refresh_token' => $env('DROPBOX_REFRESH_TOKEN'),
            'access_token' => $env('DROPBOX_ACCESS_TOKEN'),
        ],
        '4shared' => [
            'api_key' => $env('FOURSHARED_API_KEY'),
            'username' => $env('FOURSHARED_USERNAME'),
            'password' => $env('FOURSHARED_PASSWORD'),
        ],
        's3' => [
            'endpoint' => $env('S3_ENDPOINT'),
            'bucket' => $env('S3_BUCKET'),
            'key' => $env('S3_KEY'),
            'secret' => $env('S3_SECRET'),
            'region' => $env('S3_REGION', 'us-east-1'),
        ],
    ],

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
            'required' => ['client_id', 'client_secret', 'refresh_token'],
            'alternatives' => [
                ['label' => 'OAuth2 refresh token', 'fields' => ['client_id', 'client_secret', 'refresh_token']],
                ['label' => 'Service account', 'fields' => ['service_account_email', 'service_account_private_key']],
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
                ['label' => 'App + refresh token', 'fields' => ['app_key', 'app_secret', 'refresh_token']],
                ['label' => 'Access token', 'fields' => ['access_token']],
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