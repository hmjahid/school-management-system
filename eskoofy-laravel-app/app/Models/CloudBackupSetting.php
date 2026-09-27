<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per install: the cloud provider, its encrypted credentials and the
 * automatic-backup policy. Created lazily on first write
 * (see App\Services\CloudBackup\CloudBackupService::settings()).
 */
class CloudBackupSetting extends Model
{
    protected $fillable = [
        'provider',
        'credentials',
        'folder',
        'is_enabled',
        'auto_enabled',
        'interval_minutes',
        'keep',
        'last_run_at',
        'last_status',
        'last_error',
    ];

    /**
     * Credentials are always encrypted at rest. `credentials` is a JSON map of
     * provider fields (see config/backup.php `providers.*.fields`).
     */
    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_enabled' => 'boolean',
        'auto_enabled' => 'boolean',
        'interval_minutes' => 'integer',
        'keep' => 'integer',
        'last_run_at' => 'datetime',
    ];

    /**
     * Credential fields that are configured (values are never returned).
     *
     * @return array<string, bool>
     */
    public function configuredFields(): array
    {
        $credentials = $this->credentials ?: [];
        $fields = config('backup.providers.'.$this->provider.'.fields', []);

        $configured = [];
        foreach (array_keys($fields) as $field) {
            $configured[$field] = ! empty($credentials[$field]);
        }

        return $configured;
    }

    /**
     * Whether this install has a complete credential set for its provider.
     *
     * Delegates to the manager because a provider may accept several
     * *alternative* credential sets (Google Drive: OAuth2 refresh token *or*
     * service account), so checking the flat `required` list would report a
     * perfectly valid service-account install as unconfigured.
     */
    public function hasRequiredCredentials(): bool
    {
        return app(\App\Services\CloudBackup\CloudBackupManager::class)->isConfigured(
            (string) $this->provider,
            (array) ($this->credentials ?: [])
        );
    }
}
