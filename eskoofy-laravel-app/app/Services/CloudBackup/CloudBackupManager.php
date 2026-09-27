<?php

namespace App\Services\CloudBackup;

use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use App\Services\CloudBackup\Contracts\CloudHttpClient;
use App\Services\CloudBackup\Drivers\DropboxDriver;
use App\Services\CloudBackup\Drivers\FourSharedDriver;
use App\Services\CloudBackup\Drivers\GoogleDriveDriver;
use App\Services\CloudBackup\Drivers\LocalCloudDriver;
use App\Services\CloudBackup\Drivers\S3CloudDriver;
use RuntimeException;

/**
 * Resolves a provider key to a driver instance. Adding a provider = one driver
 * class + one line in the factory map + one entry in config/backup.php.
 */
class CloudBackupManager
{
    private const DRIVERS = [
        'google_drive' => GoogleDriveDriver::class,
        'dropbox' => DropboxDriver::class,
        '4shared' => FourSharedDriver::class,
        's3' => S3CloudDriver::class,
        'local' => LocalCloudDriver::class,
    ];

    public function __construct(private ?CloudHttpClient $http = null) {}

    /** @return array<int, string> */
    public static function providers(): array
    {
        return array_keys(config('backup.providers', self::DRIVERS));
    }

    public static function isKnown(?string $provider): bool
    {
        return $provider !== null && array_key_exists($provider, self::DRIVERS);
    }

    public static function label(?string $provider): string
    {
        return (string) (config('backup.providers.'.($provider ?: 'local').'.label') ?? $provider ?: 'Unknown');
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function driver(string $provider, array $credentials = [], ?string $folder = null): CloudBackupDriver
    {
        $provider = self::isKnown($provider) ? $provider : 'local';
        $class = self::DRIVERS[$provider];

        return new $class(
            $this->http ?? app(CloudHttpClient::class),
            $credentials,
            $folder,
        );
    }

    /**
     * Credentials for a provider: stored values first, env fallbacks underneath.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function credentialsFor(string $provider, array $stored = []): array
    {
        $fallback = (array) (config('backup.env.'.$provider, []));

        return array_filter(
            array_merge($fallback, $stored),
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * Whether a provider has enough credentials to be worth attempting.
     *
     * A provider may declare several *alternative* credential sets (Google
     * Drive accepts either an OAuth2 refresh token or a service account;
     * Dropbox either an app + refresh token or an access token). One complete
     * set is enough — checking only `required` would report Google Drive as
     * unconfigured for a perfectly valid service-account install.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function isConfigured(string $provider, array $credentials): bool
    {
        $credentials = array_filter($credentials, static fn ($value) => $value !== null && $value !== '');
        $config = (array) config('backup.providers.'.$provider, []);

        if (($config['fields'] ?? []) === []) {
            // No credentials at all (e.g. the local provider) = always ready.
            return true;
        }

        $sets = (array) ($config['alternatives'] ?? []);
        if ($sets === []) {
            $sets = [['fields' => (array) ($config['required'] ?? [])]];
        }

        foreach ($sets as $set) {
            $fields = (array) ($set['fields'] ?? []);
            $complete = $fields !== [];
            foreach ($fields as $field) {
                if (! array_key_exists($field, $credentials)) {
                    $complete = false;
                    break;
                }
            }
            if ($complete) {
                return true;
            }
        }

        return false;
    }

    /** @throws RuntimeException when the provider is not registered. */
    public function require(string $provider): CloudBackupDriver
    {
        if (! self::isKnown($provider)) {
            throw new RuntimeException("Unknown cloud backup provider: {$provider}");
        }

        return $this->driver($provider);
    }
}
