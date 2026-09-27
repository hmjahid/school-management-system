<?php

namespace App\Console\Commands;

use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Console\Command;

class CloudBackupRunCommand extends Command
{
    protected $signature = 'backup:cloud {--provider= : Override the configured provider} {--no-prune : Skip remote retention pruning}';

    protected $description = 'Create a portable backup and upload it to the configured cloud provider (Google Drive, Dropbox, 4shared, S3, local).';

    public function handle(CloudBackupService $service): int
    {
        $setting = $service->settings();
        $provider = (string) ($this->option('provider') ?: $setting->provider);

        $result = $service->run(
            $provider === $setting->provider ? $setting : tap($setting, fn ($s) => $s->provider = $provider),
            prune: ! $this->option('no-prune'),
        );

        $this->line($result['status'].': '.$result['message']);

        return $result['status'] === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
