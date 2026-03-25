<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {--path=storage/backups : Output directory}';

    protected $description = 'Create a lightweight backup archive (DB + storage/app/public).';

    public function handle(): int
    {
        $outDir = base_path($this->option('path'));
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

        // DB backup (best-effort).
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $db = config('database.connections.sqlite.database');
            if ($db && $db !== ':memory:' && file_exists($db)) {
                $zip->addFile($db, 'database/sqlite.sqlite');
            } else {
                $this->warn('SQLite DB is in-memory or missing; DB file not included.');
            }
        } else {
            $this->warn("DB driver '{$driver}' detected; DB dump not included (configure external dumps like mysqldump/pg_dump).");
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
