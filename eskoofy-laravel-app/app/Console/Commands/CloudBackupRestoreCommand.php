<?php

namespace App\Console\Commands;

use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Console\Command;

class CloudBackupRestoreCommand extends Command
{
    protected $signature = 'backup:cloud:restore {file? : Remote id/name} {--latest : Restore the newest remote file}';

    protected $description = 'Restore the database and uploads from a cloud backup copy.';

    public function handle(CloudBackupService $service): int
    {
        $remoteId = (string) ($this->argument('file') ?? '');

        if ($remoteId === '' && $this->option('latest')) {
            $listing = $service->listRemote();

            if (! $listing['ok'] && $listing['message'] !== '') {
                // An unconfigured provider or a failed call is a different problem
                // from "no backups yet", and the operator needs to know which.
                $this->error($listing['message']);

                return self::FAILURE;
            }

            $newest = $listing['files'][0] ?? null;
            if ($newest === null) {
                $this->error('No remote backups found.');

                return self::FAILURE;
            }
            $remoteId = (string) $newest['id'];
        }

        if ($remoteId === '') {
            $this->error('Pass a remote file id or use --latest.');

            return self::FAILURE;
        }

        $result = $service->restore($remoteId);
        $result['ok'] ? $this->info($result['message']) : $this->error($result['message']);

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
