<?php

namespace App\Console\Commands;

use App\Services\PortableBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore {file : Backup zip filename in storage/backups} {--force : Skip confirmation}';

    protected $description = 'Restore a backup created by backup:run (portable DB dump + storage/app/public).';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $backupDir = 'backups';
        $file = $this->argument('file');
        $path = $backupDir.'/'.$file;

        if (! $disk->exists($path)) {
            $this->error("Backup not found: {$path}");

            return self::FAILURE;
        }

        $fullPath = $disk->path($path);
        if (! str_ends_with($fullPath, '.zip')) {
            $this->error('Backup must be a .zip file.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Restore '.$file.'? Existing files and database rows will be overwritten.')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== true) {
            $this->error('Failed to open zip.');

            return self::FAILURE;
        }

        $manifestJson = $zip->getFromName(PortableBackupService::MANIFEST_FILE);
        if ($manifestJson !== false) {
            $this->restorePortable($zip, $manifestJson);
        } else {
            $this->restoreLegacy($zip);
        }

        $zip->close();

        $this->info("Restored from {$file}.");

        return self::SUCCESS;
    }

    private function restorePortable(ZipArchive $zip, string $manifestJson): void
    {
        $service = app(PortableBackupService::class);
        $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
        if (! $service->isValidManifest($manifest)) {
            throw new \RuntimeException('Unrecognized portable backup format.');
        }

        $tablesJson = $zip->getFromName(PortableBackupService::TABLES_FILE);
        if ($tablesJson === false) {
            throw new \RuntimeException('Portable backup is missing database/tables.json.');
        }

        $this->extractPublicStorage($zip);
        $cleared = $service->restoreTables(
            $service->parseTables($tablesJson),
            $manifest['sensitiveColumns'] ?? null,
            $manifest['cipherFingerprint'] ?? null,
        );
        $this->info('Restored '.$manifest['tableCount'].' tables from portable dump.');

        foreach ($cleared as $column) {
            $this->warn("Cleared encrypted column {$column}: the backup's app key does not match this install, so the value cannot be decrypted here.");
        }

        $sourceVariant = $manifest['eskoofyVariant'] ?? null;
        $targetVariant = config('eskoolfy.variant', 'bd');
        if ($sourceVariant !== null && $sourceVariant !== $targetVariant) {
            foreach ($service->reconcileVariant($sourceVariant) as $note) {
                $this->info($note);
            }
            $this->warn("This backup was created on the '{$sourceVariant}' variant and is being restored on the '{$targetVariant}' variant — variant settings were reconciled to this profile (gateways, currency, payment defaults).");
        } elseif ($sourceVariant !== null) {
            $this->info("Restored a '{$sourceVariant}' variant backup (same variant — restored verbatim).");
        } else {
            $this->warn('This backup predates the variant tags in the manifest; restored verbatim.');
        }
    }

    private function restoreLegacy(ZipArchive $zip): void
    {
        // Pre-portable archives only stored database/sqlite.sqlite + storage/app/public/...
        $this->extractPublicStorage($zip);

        $sqlite = $zip->getFromName('database/sqlite.sqlite');
        if ($sqlite !== false) {
            $db = config('database.connections.sqlite.database');
            if ($db && $db !== ':memory:' && File::exists($db)) {
                File::copy($db, $db.'.bak');
                File::put($db, $sqlite);
                $this->info('SQLite database replaced (previous copy kept at '.$db.'.bak).');
            } else {
                $this->warn('Legacy sqlite file skipped: no writable sqlite database configured.');
            }
        }
    }

    /**
     * Strip the storage/app/public/ prefix and restore uploads in place.
     */
    private function extractPublicStorage(ZipArchive $zip): void
    {
        $publicPath = storage_path('app/public');
        File::ensureDirectoryExists($publicPath);
        $prefix = 'storage/app/public/';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! str_starts_with($name, $prefix)) {
                continue;
            }
            $target = $publicPath.DIRECTORY_SEPARATOR.substr($name, strlen($prefix));
            if (str_ends_with($name, '/')) {
                File::ensureDirectoryExists(rtrim($target, '/'));

                continue;
            }
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $zip->getFromIndex($i));
        }
    }
}
