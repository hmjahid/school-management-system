<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\SecretCipher;

/**
 * One row per install: the cloud provider, its encrypted credentials and the
 * automatic-backup policy. Created lazily on first write.
 */
class CloudBackupSetting extends Model
{
    protected static string $table = 'cloud_backup_settings';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;
    protected static bool $timestamps = true;

    protected array $fillable = [
        'provider', 'credentials', 'folder', 'is_enabled', 'auto_enabled',
        'interval_minutes', 'keep', 'last_run_at', 'last_status', 'last_error',
    ];

    protected array $casts = [
        'is_enabled' => 'boolean',
        'auto_enabled' => 'boolean',
        'interval_minutes' => 'integer',
        'keep' => 'integer',
    ];

    /**
     * Credentials are always encrypted at rest (JSON map of provider fields).
     * Intercepting get/set here (instead of accessors) is deliberate: the base
     * Model resolves an existing attribute through castAttribute() before it
     * ever consults accessors, so a plain getCredentialsAttribute() would never
     * run for a row already loaded from the database.
     */
    public function getAttribute(string $key): mixed
    {
        if ($key === 'credentials' && array_key_exists($key, $this->attributes)) {
            $raw = $this->attributes[$key];

            if ($raw === null || $raw === '') {
                return [];
            }

            $json = SecretCipher::decrypt((string) $raw);

            if ($json === null) {
                return [];
            }

            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : [];
        }

        return parent::getAttribute($key);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        if ($key === 'credentials') {
            $value = is_array($value) ? $value : [];
            $this->attributes[$key] = SecretCipher::encrypt(json_encode($value));
        } else {
            parent::setAttribute($key, $value);
        }
    }

    /**
     * `fill()` writes attributes directly, bypassing setAttribute(). Route
     * credentials through it so every write path encrypts the same way.
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function configuredFields(): array
    {
        $credentials = (array) ($this->credentials ?? []);
        $fields = config('backup.providers.' . ($this->provider ?? 'local') . '.fields', []);

        $configured = [];
        foreach (array_keys($fields) as $field) {
            $configured[$field] = ! empty($credentials[$field]);
        }

        return $configured;
    }

    public function hasRequiredCredentials(): bool
    {
        return (new \App\Services\CloudBackup\CloudBackupManager())->isConfigured(
            (string) ($this->provider ?? 'local'),
            (array) ($this->credentials ?? [])
        );
    }
}