<?php

declare(strict_types=1);

namespace App\Services\CloudBackup;

use App\Core\DatabaseInterface;
use App\Core\Support\Carbon;
use App\Core\Support\Str;
use App\Models\CloudBackupRun;
use App\Models\CloudBackupSetting;
use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use App\Services\PortableBackupService;
use RuntimeException;
use Throwable;

/**
 * Orchestrates the cloud backup feature for the raw-PHP port: settings
 * persistence, the interval dispatcher, uploading a fresh portable backup,
 * retention pruning, and restoring a cloud copy with the shared portable-
 * restore contract.
 *
 * Port of eskoofy-laravel-app/app/Services/CloudBackup/CloudBackupService.php.
 * Differences from the Laravel version, forced by the shared-hosting runtime:
 *  - no Cache facade: the upload lock is a locked file under storage/;
 *  - no Artisan: portable archives are produced directly by PortableBackupService;
 *  - no `report()`: failures are logged to storage/logs/php-errors.log.
 */
class CloudBackupService
{
    public function __construct(private readonly CloudBackupManager $manager, private readonly DatabaseInterface $db)
    {
    }

    // ---------------------------------------------------------------- settings

    public function settings(): CloudBackupSetting
    {
        $setting = CloudBackupSetting::query()->first();
        if ($setting) {
            return $setting;
        }

        $auto = (array) config('backup.auto', []);

        return CloudBackupSetting::create([
            'provider' => (string) config('backup.default_provider', 'local'),
            'folder' => (string) config('backup.folder', 'eskoofy-backups'),
            'is_enabled' => true,
            'auto_enabled' => (bool) ($auto['enabled'] ?? false),
            'interval_minutes' => (int) ($auto['interval_minutes'] ?? 60),
            'keep' => (int) ($auto['keep'] ?? 7),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveSettings(array $input): CloudBackupSetting
    {
        $setting = $this->settings();
        $auto = (array) config('backup.auto', []);

        $provider = (string) ($input['provider'] ?? $setting->provider);
        if (! CloudBackupManager::isKnown($provider)) {
            $provider = (string) config('backup.default_provider', 'local');
        }

        $credentials = $this->mergeCredentials($setting, $provider, (array) ($input['credentials'] ?? []));

        $setting->fill([
            'provider' => $provider,
            'credentials' => $credentials,
            'folder' => trim((string) ($input['folder'] ?? '')) ?: (string) config('backup.folder', 'eskoofy-backups'),
            'is_enabled' => (bool) ($input['is_enabled'] ?? $setting->is_enabled),
            'auto_enabled' => (bool) ($input['auto_enabled'] ?? false),
            'interval_minutes' => $this->clamp(
                (int) ($input['interval_minutes'] ?? $setting->interval_minutes),
                (int) ($auto['min_interval_minutes'] ?? 5),
                (int) ($auto['max_interval_minutes'] ?? 10080)
            ),
            'keep' => $this->clamp(
                (int) ($input['keep'] ?? $setting->keep),
                (int) ($auto['min_keep'] ?? 1),
                (int) ($auto['max_keep'] ?? 365)
            ),
        ])->save();

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function previewSettings(array $input): CloudBackupSetting
    {
        $stored = $this->settings();

        $preview = new CloudBackupSetting($stored->toArray());

        $provider = (string) ($input['provider'] ?? $stored->provider);
        if (! CloudBackupManager::isKnown($provider)) {
            $provider = (string) config('backup.default_provider', 'local');
        }

        $preview->provider = $provider;
        $preview->folder = trim((string) ($input['folder'] ?? '')) ?: (string) $stored->folder;
        $preview->credentials = $this->mergeCredentials($stored, $provider, (array) ($input['credentials'] ?? []));

        return $preview;
    }

    private function mergeCredentials(CloudBackupSetting $stored, string $provider, array $submitted): array
    {
        $credentials = $stored->provider === $provider ? (array) ($stored->credentials ?? []) : [];

        foreach ($submitted as $field => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $credentials[$field] = trim($value);
        }

        $fields = array_keys((array) config('backup.providers.'.$provider.'.fields', []));

        return array_filter(
            array_intersect_key($credentials, array_flip($fields)),
            static fn ($value) => is_string($value) && trim($value) !== ''
        );
    }

    // ---------------------------------------------------------------- drivers

    public function driver(?CloudBackupSetting $setting = null): CloudBackupDriver
    {
        $setting ??= $this->settings();

        return $this->manager->driver(
            (string) $setting->provider,
            $this->credentialsFor($setting),
            (string) $setting->folder
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function credentialsFor(CloudBackupSetting $setting): array
    {
        return $this->manager->credentialsFor(
            (string) $setting->provider,
            (array) ($setting->credentials ?? [])
        );
    }

    public function isConfigured(?CloudBackupSetting $setting = null): bool
    {
        $setting ??= $this->settings();

        return $this->manager->isConfigured((string) $setting->provider, $this->credentialsFor($setting));
    }

    /** @return array{ok: bool, message: string} */
    public function testConnection(?CloudBackupSetting $setting = null): array
    {
        try {
            return $this->driver($setting)->test();
        } catch (Throwable $e) {
            $this->log($e);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ---------------------------------------------------------------- uploads

    /**
     * @return array{status: string, message: string, file: string|null, remote_id: string|null}
     */
    public function run(?CloudBackupSetting $setting = null, bool $prune = true): array
    {
        $setting ??= $this->settings();
        $provider = (string) $setting->provider;

        if (! $setting->is_enabled) {
            return $this->record($setting, 'skipped', 'Cloud backup is disabled.', null, null, 0, false);
        }

        if (! CloudBackupManager::isKnown($provider)) {
            return $this->record($setting, 'skipped', "Unknown provider '{$provider}'.", null, null, 0, false);
        }

        if (! $this->isConfigured($setting)) {
            return $this->record(
                $setting,
                'skipped',
                'Cloud backup provider "'.CloudBackupManager::label($provider).'" has no complete credential set.',
                null,
                null,
                0,
                false
            );
        }

        if (! $this->lock()) {
            return $this->record($setting, 'skipped', 'Another cloud backup is already running.', null, null, 0, false);
        }

        try {
            $file = $this->createPortableBackup();
            $result = $this->driver($setting)->upload($file, basename($file));

            $this->discardStagedArchive($file);

            if (isset($result['error'])) {
                return $this->record($setting, 'failed', (string) $result['error'], basename($file), null, 0);
            }

            $driver = $this->driver($setting);
            $remoteId = $driver->remoteKey($result);
            $size = (int) ($result['size'] ?? 0);
            $message = 'Uploaded to '.CloudBackupManager::label($provider).' ('.$remoteId.').';

            if ($prune) {
                $removed = $this->prune((int) $setting->keep);
                $message .= ' Retention: kept the newest '.(int) $setting->keep.' ('.$removed.' removed).';
            }

            return $this->record($setting, 'success', $message, basename($file), $remoteId, $size);
        } catch (Throwable $e) {
            $this->log($e);

            return $this->record($setting, 'failed', $e->getMessage());
        } finally {
            $this->pruneStaged();
            $this->unlock();
        }
    }

    private function discardStagedArchive(string $file): void
    {
        $staging = $this->stagingDirectory();

        if (str_starts_with($file, $staging.DIRECTORY_SEPARATOR) && is_file($file)) {
            @unlink($file);
        }
    }

    private function stagingDirectory(): string
    {
        return rtrim(
            storage_path((string) config('backup.scratch_dir', 'cloud-staging')),
            DIRECTORY_SEPARATOR
        );
    }

    private function pruneStaged(int $keep = 3): void
    {
        $keep = max(1, $keep);
        $dir = $this->stagingDirectory();
        if (! is_dir($dir)) {
            return;
        }

        $files = [];
        foreach ((array) glob($dir.'/*.zip') as $path) {
            $files[$path] = (int) @filemtime((string) $path);
        }
        arsort($files);

        foreach (array_slice(array_keys($files), $keep) as $path) {
            @unlink((string) $path);
        }
    }

    /**
     * @return array{status: string, message: string, file: string|null, remote_id: string|null}
     */
    public function dispatch(?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $setting = $this->settings();

        if (! $setting->is_enabled || ! $setting->auto_enabled) {
            return $this->record($setting, 'skipped', 'Automatic cloud backup is off.', null, null, 0, false);
        }

        if (! $this->isDue($setting, $now)) {
            return [
                'status' => 'skipped',
                'message' => 'Not due yet (interval '.$setting->interval_minutes.' min).',
                'file' => null,
                'remote_id' => null,
            ];
        }

        return $this->run($setting);
    }

    public function isDue(CloudBackupSetting $setting, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now();
        if (! $setting->last_run_at) {
            return true;
        }

        $last = $setting->last_run_at instanceof Carbon
            ? $setting->last_run_at
            : Carbon::parse($setting->last_run_at);

        return $last->getTimestamp() + ((int) $setting->interval_minutes * 60) <= $now->getTimestamp();
    }

    // --------------------------------------------------------------- retention

    public function prune(int $keep, ?CloudBackupSetting $setting = null): int
    {
        $keep = max(1, $keep);

        try {
            $files = $this->driver($setting)->list();
        } catch (Throwable $e) {
            $this->log($e);

            return 0;
        }

        $removed = 0;
        $driver = $this->driver($setting);
        foreach (array_slice($files, $keep) as $file) {
            $id = $driver->remoteKey($file);
            if ($id !== '' && $driver->delete($id)) {
                $removed++;
            }
        }

        $this->pruneLocal($keep);

        return $removed;
    }

    public function pruneLocal(int $keep): int
    {
        $keep = max(1, $keep);
        $dir = storage_path('backups');

        $files = [];
        foreach ((array) glob($dir.'/*.zip') as $path) {
            if (! is_file((string) $path)) {
                continue;
            }
            $files[$path] = (int) @filemtime((string) $path);
        }
        arsort($files);

        $removed = 0;
        foreach (array_slice(array_keys($files), $keep) as $path) {
            if (@unlink((string) $path)) {
                $removed++;
            }
        }

        $this->pruneStaged();

        return $removed;
    }

    // ---------------------------------------------------------------- restores

    /**
     * @return array{ok: bool, message: string, file: string|null}
     */
    public function restore(string $remoteId, ?CloudBackupSetting $setting = null): array
    {
        $setting ??= $this->settings();
        $target = storage_path('backups/'.Str::random(10).'_cloud.zip');
        $dir = dirname($target);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $path = $this->driver($setting)->download($remoteId, $target);
        if ($path === null) {
            return ['ok' => false, 'message' => 'Could not download that file from the provider.', 'file' => null];
        }

        try {
            $message = $this->restoreFromFile($path);

            return ['ok' => true, 'message' => 'Restore completed. '.$message, 'file' => basename($path)];
        } catch (Throwable $e) {
            $this->log($e);

            return ['ok' => false, 'message' => $e->getMessage(), 'file' => basename($path)];
        } finally {
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

    /**
     * Restore a portable zip: tables + storage/app/public, with cross-variant
     * reconciliation when the source variant differs.
     */
    public function restoreFromFile(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Not a readable zip archive.');
        }

        $manifestRaw = $zip->getFromName(PortableBackupService::MANIFEST_FILE);
        $tablesRaw = $zip->getFromName(PortableBackupService::TABLES_FILE);
        $zip->close();

        if ($manifestRaw === false || $tablesRaw === false) {
            throw new RuntimeException('Not a portable Eskoofy backup (missing MANIFEST.json / database/tables.json).');
        }

        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest) || ! (new PortableBackupService($this->db))->isValidManifest($manifest)) {
            throw new RuntimeException('Unsupported backup format.');
        }

        $tables = (new PortableBackupService($this->db))->parseTables($tablesRaw);
        (new PortableBackupService($this->db))->restoreTables($tables);

        $this->restoreStorageFiles($path);
        $this->extractTo($path, 'storage/app/public', storage_path('app/public'));

        $sourceVariant = is_string($manifest['eskoofyVariant'] ?? null) ? $manifest['eskoofyVariant'] : null;
        $notes = (new PortableBackupService($this->db))->reconcileVariant($sourceVariant);

        $message = 'Restored '.count($tables).' tables from portable dump.';
        if ($sourceVariant !== null && $sourceVariant !== (string) (config('eskoolfy.variant') ?? 'bd')) {
            $message .= " Restored a '{$sourceVariant}' variant backup (cross-variant: reconciled).";
            if ($notes !== []) {
                $message .= ' '.implode(' | ', array_slice($notes, 0, 8)).(count($notes) > 8 ? '…' : '');
            }
        } else {
            $message .= " Restored a '".($sourceVariant ?? '?')."' variant backup (same variant — restored verbatim).";
        }

        return $message;
    }

    /** @return array{ok: bool, message: string, files: array<int, array<string, mixed>>} */
    public function listRemote(?CloudBackupSetting $setting = null): array
    {
        $setting ??= $this->settings();

        if (! $setting->is_enabled) {
            return ['ok' => false, 'message' => 'Cloud backup is disabled.', 'files' => []];
        }

        if (! CloudBackupManager::isKnown((string) $setting->provider)) {
            return ['ok' => false, 'message' => "Unknown provider '{$setting->provider}'.", 'files' => []];
        }

        if (! $this->isConfigured($setting)) {
            return [
                'ok' => false,
                'message' => 'Provider "'.CloudBackupManager::label((string) $setting->provider).'" has no complete credential set.',
                'files' => [],
            ];
        }

        try {
            $files = array_values((array) $this->driver($setting)->list());
        } catch (Throwable $e) {
            $this->log($e);

            return ['ok' => false, 'message' => $e->getMessage(), 'files' => []];
        }

        return [
            'ok' => true,
            'message' => $files === []
                ? 'No backups on the provider yet.'
                : count($files).' backup(s) on the provider.',
            'files' => $files,
        ];
    }

    public function deleteRemote(string $remoteId, ?CloudBackupSetting $setting = null): bool
    {
        try {
            return $this->driver($setting)->delete($remoteId);
        } catch (Throwable $e) {
            $this->log($e);

            return false;
        }
    }

    // ------------------------------------------------------------------ history

    /** @return array<int, CloudBackupRun> */
    public function recentRuns(int $limit = 20): array
    {
        return CloudBackupRun::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function createPortableBackup(): string
    {
        $directory = $this->stagingDirectory();
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $ts = date('Ymd_His');
        $name = 'backup_'.$ts.'_'.Str::random(6).'.zip';
        $path = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;

        $zip = (new PortableBackupService($this->db))->zip('raw-php');
        if (@file_put_contents($path, $zip) === false) {
            throw new RuntimeException("Unable to write backup archive: {$path}");
        }

        return $path;
    }

    public function newestLocalBackupName(): ?string
    {
        $dir = storage_path('backups');

        $newest = null;
        $mtime = 0;
        foreach ((array) glob($dir.'/*.zip') as $path) {
            if (! is_file((string) $path)) {
                continue;
            }
            $t = (int) @filemtime((string) $path);
            if ($t > $mtime) {
                $mtime = $t;
                $newest = basename((string) $path);
            }
        }

        return $newest;
    }

    /**
     * @return array{status: string, message: string, file: string|null, remote_id: string|null}
     */
    private function record(
        CloudBackupSetting $setting,
        string $status,
        string $message,
        ?string $file = null,
        ?string $remoteId = null,
        int $size = 0,
        bool $countsAsRun = true,
    ): array {
        CloudBackupRun::create([
            'provider' => (string) $setting->provider,
            'file_name' => $file ?? '—',
            'remote_id' => $remoteId,
            'size' => $size,
            'status' => $status,
            'message' => Str::limit($message, 2000, ''),
        ]);

        $setting->fill([
            'last_run_at' => $countsAsRun ? date('Y-m-d H:i:s') : $setting->last_run_at,
            'last_status' => $status,
            'last_error' => $status === 'failed' ? Str::limit($message, 2000, '') : null,
        ])->save();

        return [
            'status' => $status,
            'message' => $message,
            'file' => $file,
            'remote_id' => $remoteId,
        ];
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private function lock(): bool
    {
        $dir = storage_path('framework/cache');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $path = $dir.'/'.(string) config('backup.lock.key', 'eskoofy-cloud-backup').'.lock';
        $handle = @fopen($path, 'c');
        if ($handle === false) {
            return true; // cannot lock — do not block the backup entirely
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $this->lockHandle = $handle;

        return true;
    }

    private function unlock(): void
    {
        if (is_resource($this->lockHandle)) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    /** @var resource|null */
    private $lockHandle = null;

    private function log(Throwable $e): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir.'/php-errors.log',
            '['.date('Y-m-d H:i:s').'] Cloud backup: '.$e->getMessage().PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * Extract a prefix of the portable zip into a directory, creating parent
     * directories as needed and refusing to write outside the target.
     */
    private function extractTo(string $path, string $prefix, string $target): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (! str_starts_with($name, $prefix.'/')) {
                continue;
            }
            $rel = substr($name, strlen($prefix) + 1);
            if ($rel === '' || str_contains($rel, '..')) {
                continue;
            }
            $dest = rtrim($target, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (! is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0775, true);
            }
            @file_put_contents($dest, (string) $zip->getFromIndex($i));
        }

        $zip->close();
    }

    private function restoreStorageFiles(string $path): void
    {
        $this->extractTo($path, 'storage/app/public', storage_path('app/public'));
    }
}