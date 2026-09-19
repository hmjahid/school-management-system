<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore {file : Backup zip filename in storage/backups} {--force : Skip confirmation}';

    protected $description = 'Restore a backup created by backup:run (extracts storage/app/public from zip).';

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

        if (! $this->option('force') && ! $this->confirm("Restore {$file}? Existing files will be overwritten.")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $extractTo = Storage::disk('public')->path('');
        File::ensureDirectoryExists($extractTo);

        $zip = new \ZipArchive;
        if ($zip->open($fullPath) !== true) {
            $this->error('Failed to open zip.');

            return self::FAILURE;
        }

        $zip->extractTo($extractTo);
        $zip->close();

        $this->info("Restored from {$file} into {$extractTo}.");

        return self::SUCCESS;
    }
}
