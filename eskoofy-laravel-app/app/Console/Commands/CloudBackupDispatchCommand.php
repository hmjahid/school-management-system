<?php

namespace App\Console\Commands;

use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Console\Command;

/**
 * The interval dispatcher. Runs on a fixed cheap cadence (every 5 minutes) and
 * decides from the install's own `interval_minutes` setting whether a backup is
 * due — that keeps the interval user-configurable without touching cron.
 */
class CloudBackupDispatchCommand extends Command
{
    protected $signature = 'backup:cloud:dispatch {--force : Upload regardless of the interval}';

    protected $description = 'Upload a cloud backup when the configured interval has elapsed (called by the scheduler).';

    public function handle(CloudBackupService $service): int
    {
        if ($this->option('force')) {
            $result = $service->run();
        } else {
            $result = $service->dispatch();
        }

        $this->line($result['status'].': '.$result['message']);

        // A failed upload is recorded, not raised: one bad provider call must
        // never make the scheduler tick fail.
        return self::SUCCESS;
    }
}
