<?php

namespace App\Services\CloudBackup;

use App\Models\CloudBackupRun;
use App\Models\CloudBackupSetting;
use App\Services\CloudBackup\Contracts\CloudBackupDriver;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orchestrates the cloud backup feature: settings persistence, the interval
 * dispatcher, uploading a fresh portable backup, retention pruning, and
 * restoring a cloud copy with the shared portable-restore contract.
 *
 * Every run appends to `cloud_backup_runs` and updates the settings row, so the
 * admin page can show a real history and operators can audit what the
 * dispatcher did while they were asleep.
 */
class CloudBackupService
{
    public function __construct(private CloudBackupManager $manager) {}

    // ---------------------------------------------------------------- settings

    /** The install's settings row, created from config defaults on first use. */
    public function settings(): CloudBackupSetting
    {
        $setting = CloudBackupSetting::query()->first();
        if ($setting) {
            return $setting;
        }

        $auto = (array) config('backup.auto', []);

        return CloudBackupSetting::query()->create([
            'provider' => (string) config('backup.default_provider', 'local'),
            'folder' => (string) config('backup.folder', 'eskoofy-backups'),
            'is_enabled' => true,
            'auto_enabled' => (bool) ($auto['enabled'] ?? false),
            'interval_minutes' => (int) ($auto['interval_minutes'] ?? 60),
            'keep' => (int) ($auto['keep'] ?? 7),
        ]);
    }

    /**
     * Persist the settings form. Empty credential inputs keep the stored value
     * (secrets are never rendered back, so a blank field means "unchanged").
     *
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
     * An unsaved settings snapshot built the same way saveSettings() would, so
     * the admin can test a credential set before committing it to the database.
     * Never persisted.
     *
     * @param  array<string, mixed>  $input
     */
    public function previewSettings(array $input): CloudBackupSetting
    {
        $stored = $this->settings();

        $preview = new CloudBackupSetting($stored->getAttributes());
        $preview->exists = true;

        $provider = (string) ($input['provider'] ?? $stored->provider);
        if (! CloudBackupManager::isKnown($provider)) {
            $provider = (string) config('backup.default_provider', 'local');
        }

        $fields = array_keys((array) config('backup.providers.'.$provider.'.fields', []));
        $credentials = (array) ($input['credentials'] ?? []);

        $preview->provider = $provider;
        $preview->folder = trim((string) ($input['folder'] ?? '')) ?: (string) $stored->folder;
        // Same merge rules as saveSettings(), so testing a half-filled form
        // neither drops the stored secret nor borrows one from a provider the
        // form is no longer using.
        $preview->credentials = $this->mergeCredentials($stored, $provider, (array) ($input['credentials'] ?? []));

        return $preview;
    }

    /**
     * Combine stored and submitted credentials for one provider.
     *
     * Two rules, both of which matter for a settings form where secrets are
     * never rendered back:
     *  - a blank submitted field means "unchanged", not "clear";
     *  - credentials belong to the provider they were entered for. A provider
     *    switch therefore starts from an empty set, because field names do
     *    collide across providers (google_drive and dropbox both use
     *    "refresh_token") and carrying a token across would store one
     *    provider's secret under another.
     */
    private function mergeCredentials(CloudBackupSetting $stored, string $provider, array $submitted): array
    {
        $credentials = $stored->provider === $provider ? (array) ($stored->credentials ?: []) : [];

        foreach ($submitted as $field => $value) {
            // Only a filled-in string may overwrite. `null` and `''` both mean
            // "unchanged" — Laravel's ConvertEmptyStringsToNull middleware turns
            // the blank input a settings form always posts into null, so testing
            // for '' alone would store a null over a real secret.
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $credentials[$field] = trim($value);
        }

        $fields = array_keys((array) config('backup.providers.'.$provider.'.fields', []));

        // Drop credentials that belong to a different provider, and any value
        // that is somehow absent, so the stored set never gains a null entry.
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
     * The credentials a provider will actually be given: stored values, then the
     * env fallbacks from config/backup.php.
     *
     * @return array<string, mixed>
     */
    public function credentialsFor(CloudBackupSetting $setting): array
    {
        return $this->manager->credentialsFor(
            (string) $setting->provider,
            (array) ($setting->credentials ?: [])
        );
    }

    /** Does the install have a complete credential set for its provider? */
    public function isConfigured(?CloudBackupSetting $setting = null): bool
    {
        $setting ??= $this->settings();

        return $this->manager->isConfigured((string) $setting->provider, $this->credentialsFor($setting));
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(?CloudBackupSetting $setting = null): array
    {
        try {
            return $this->driver($setting)->test();
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ---------------------------------------------------------------- uploads

    /**
     * Create a portable backup and upload it to the configured provider.
     *
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

        $lock = Cache::lock((string) config('backup.lock.key', 'eskoofy-cloud-backup'), (int) config('backup.lock.seconds', 600));
        if (! $lock->get()) {
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
            report($e);

            return $this->record($setting, 'failed', $e->getMessage());
        } finally {
            $this->pruneStaged();
            $lock->release();
        }
    }

    /**
     * Remove the staged archive after a successful upload so the server does not
     * accumulate one full copy of every backup. Only files inside the staging
     * directory are ever touched, and only the exact path just uploaded.
     */
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
            Storage::disk('local')->path((string) config('backup.scratch_dir', 'cloud-staging')),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * Cap the staging directory. Failed runs keep their archive so it can be
     * inspected, so without this it would grow without bound.
     */
    private function pruneStaged(int $keep = 3): void
    {
        $keep = max(1, $keep);
        $disk = Storage::disk('local');
        $directory = (string) config('backup.scratch_dir', 'cloud-staging');

        $files = collect($disk->files($directory))
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->sortByDesc(fn ($file) => $disk->lastModified($file))
            ->values();

        foreach ($files->slice($keep) as $file) {
            $disk->delete($file);
        }
    }

    /**
     * The dispatcher entry point: uploads only when the configured interval has
     * elapsed. Never throws — a scheduler tick must not fail the whole run.
     *
     * @return array{status: string, message: string, file: string|null, remote_id: string|null}
     */
    public function dispatch(?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();
        $setting = $this->settings();

        if (! $setting->is_enabled || ! $setting->auto_enabled) {
            return $this->record($setting, 'skipped', 'Automatic cloud backup is off.', null, null, 0, false);
        }

        if (! $this->isDue($setting, $now)) {
            // Not due is the common case on every 5-minute tick: record it in
            // memory only. Writing a run row (and bumping last_run_at) here would
            // both flood the history and keep resetting the interval clock.
            return [
                'status' => 'skipped',
                'message' => 'Not due yet (interval '.$setting->interval_minutes.' min).',
                'file' => null,
                'remote_id' => null,
            ];
        }

        return $this->run($setting);
    }

    /** Has the configured interval elapsed since the last successful run? */
    public function isDue(CloudBackupSetting $setting, ?CarbonInterface $now = null): bool
    {
        $now ??= Carbon::now();
        if (! $setting->last_run_at) {
            return true;
        }

        return $setting->last_run_at->copy()->addMinutes((int) $setting->interval_minutes)->lessThanOrEqualTo($now);
    }

    // --------------------------------------------------------------- retention

    /**
     * Keep the newest `$keep` remote files; delete the rest. Returns how many
     * were removed.
     */
    public function prune(int $keep, ?CloudBackupSetting $setting = null): int
    {
        $keep = max(1, $keep);

        try {
            $files = $this->driver($setting)->list();
        } catch (Throwable $e) {
            report($e);

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

    /** Keep the newest `$keep` local portable zips in `storage/app/backups`. */
    /**
     * Prune the archives a human created from the Backups page. Ordered by
     * modification time, not by name: these files do not all share a naming
     * scheme, and a lexicographic sort would let an old archive with a
     * high-sorting name ("real_backup_2026…") permanently displace the newest
     * real backup.
     */
    public function pruneLocal(int $keep): int
    {
        $keep = max(1, $keep);
        $disk = Storage::disk('local');

        $files = collect($disk->files('backups'))
            ->filter(fn ($file) => str_ends_with($file, '.zip') && is_file($disk->path($file)))
            ->sortByDesc(fn ($file) => $disk->lastModified($file))
            ->values();

        $removed = 0;
        foreach ($files->slice($keep) as $file) {
            if ($disk->delete($file)) {
                $removed++;
            }
        }

        $this->pruneStaged();

        return $removed;
    }

    // ---------------------------------------------------------------- restores

    /**
     * Restore from a cloud copy: download the remote file into `backups/` and
     * hand it to the shared portable-restore path.
     *
     * @return array{ok: bool, message: string, file: string|null}
     */
    public function restore(string $remoteId, ?CloudBackupSetting $setting = null): array
    {
        $setting ??= $this->settings();
        $target = Storage::disk('local')->path('backups/'.Str::random(10).'_cloud.zip');

        $path = $this->driver($setting)->download($remoteId, $target);
        if ($path === null) {
            return ['ok' => false, 'message' => 'Could not download that file from the provider.', 'file' => null];
        }

        try {
            \Artisan::call('backup:restore', ['file' => basename($path), '--force' => true]);
            $output = trim(\Artisan::output());

            return ['ok' => true, 'message' => 'Restore completed. '.$output, 'file' => basename($path)];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => $e->getMessage(), 'file' => basename($path)];
        } finally {
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

    /**
     * The provider's current file list, plus enough context to explain an empty
     * result. A bare `[]` cannot distinguish "no backups yet" from "credentials
     * are missing" from "the provider call failed", and the admin page needs to
     * tell those apart.
     *
     * @return array{ok: bool, message: string, files: array<int, array<string, mixed>>}
     */
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
            report($e);

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
            report($e);

            return false;
        }
    }

    // ------------------------------------------------------------------ history

    /** @return array<int, CloudBackupRun> */
    public function recentRuns(int $limit = 20): array
    {
        return CloudBackupRun::query()->latest()->limit($limit)->get()->all();
    }

    /** Build the portable zip via the existing `backup:run` command. */
    /**
     * Create a fresh portable archive and return its absolute path.
     *
     * `backup:run` is asked for an explicit output directory and a random name,
     * then the path is returned by asking the command itself — the archive this
     * uploads is always the one just created. Picking "the newest file in
     * backups/" instead would happily upload a stale archive whenever the folder
     * also holds archives from another source.
     */
    public function createPortableBackup(): string
    {
        // --path is resolved against base_path() by the command, so the absolute
        // storage path is passed and the same value is used to read the result
        // back. Deriving the two independently is how a path mismatch sneaks in.
        $directory = Storage::disk('local')->path((string) config('backup.scratch_dir', 'cloud-staging'));

        $exitCode = \Artisan::call('backup:run', ['--path' => $directory]);
        if ($exitCode !== 0) {
            throw new RuntimeException('backup:run failed: '.trim(\Artisan::output()));
        }

        $name = $this->parseCreatedArchive(\Artisan::output());
        if ($name === null) {
            throw new RuntimeException('backup:run did not report the archive it created.');
        }

        $path = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.$name;
        if (! is_file($path)) {
            throw new RuntimeException("backup:run reported {$name} but it is not on disk.");
        }

        return $path;
    }

    /**
     * Pull the archive name out of `backup:run`'s "Backup created: <path>" line.
     * Anything unparseable returns null rather than a guess.
     */
    private function parseCreatedArchive(string $output): ?string
    {
        if (preg_match('#Backup created:\s*(\S+\.zip)#i', $output, $matches) !== 1) {
            return null;
        }

        return basename($matches[1]);
    }

    /**
     * Newest archive by modification time — used to offer "restore the latest",
     * where recency is the only sensible ordering. (Deliberately not used to
     * identify a freshly created archive; see createPortableBackup().)
     */
    public function newestLocalBackupName(): ?string
    {
        $disk = Storage::disk('local');

        $newest = collect($disk->files('backups'))
            ->filter(fn ($file) => str_ends_with($file, '.zip') && is_file($disk->path($file)))
            ->sortByDesc(fn ($file) => $disk->lastModified($file))
            ->first();

        return $newest ? basename($newest) : null;
    }

    /**
     * Append to the run history and update the settings row.
     *
     * `$countsAsRun` is false for the "we did not even try" outcomes (disabled,
     * unknown provider, lock held). Those must not move `last_run_at`, or the
     * interval would restart every time the dispatcher declined to act.
     *
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
        CloudBackupRun::query()->create([
            'provider' => (string) $setting->provider,
            'file_name' => $file ?? '—',
            'remote_id' => $remoteId,
            'size' => $size,
            'status' => $status,
            'message' => Str::limit($message, 2000, ''),
        ]);

        $setting->forceFill([
            'last_run_at' => $countsAsRun ? Carbon::now() : $setting->last_run_at,
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
}
