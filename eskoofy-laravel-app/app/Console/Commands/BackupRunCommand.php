<?php

namespace App\Console\Commands;

use App\Services\PortableBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {--path= : Output directory (defaults to local disk "backups" dir)}';

    protected $description = 'Create a lightweight backup archive (portable DB dump + storage/app/public).';

    public function handle(): int
    {
        $outDir = $this->option('path')
            ? base_path($this->option('path'))
            : Storage::disk('local')->path('backups');
        File::ensureDirectoryExists($outDir);

        $ts = now()->format('Ymd_His');
        $name = 'backup_'.$ts.'_'.Str::random(6).'.zip';
        $zipPath = rtrim($outDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            $this->error("Unable to create archive: {$zipPath}");

            return self::FAILURE;
        }

        // Storage public uploads (documents, news images, gallery, admissions docs, etc.)
        $publicPath = storage_path('app/public');
        if (is_dir($publicPath)) {
            $this->zipDir($zip, $publicPath, 'storage/app/public');
        }

        // Portable DB dump readable by every Eskoofy variant (MANIFEST.json + database/tables.json).
        $dump = app(PortableBackupService::class)->dump('laravel');
        $zip->addFromString(PortableBackupService::MANIFEST_FILE, json_encode($dump['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->addFromString(PortableBackupService::TABLES_FILE, json_encode($dump['tables'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Legacy self-contained sqlite DB file (kept for direct .sqlite restores).
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $db = config('database.connections.sqlite.database');
            if ($db && $db !== ':memory:' && file_exists($db)) {
                $zip->addFile($db, 'database/sqlite.sqlite');
            } else {
                $this->warn('SQLite DB is in-memory or missing; using portable tables.json only.');
            }
        }

        $zip->close();

        $this->info("Backup created: {$zipPath}");

        return self::SUCCESS;
    }

    private function zipDir(ZipArchive $zip, string $dir, string $prefix): void
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $files = File::allFiles($dir);

        foreach ($files as $file) {
            $rel = ltrim(Str::after($file->getPathname(), $dir), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), $prefix.'/'.$rel);
        }
    }
}
