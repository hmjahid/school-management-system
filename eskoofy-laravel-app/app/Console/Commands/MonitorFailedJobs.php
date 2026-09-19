<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorFailedJobs extends Command
{
    protected $signature = 'queue:monitor-failed {--since= : Show failures newer than this many minutes (default 10)}';

    protected $description = 'Report failed queue jobs since N minutes to the default log channel';

    public function handle(): int
    {
        $sinceMinutes = (int) ($this->option('since') ?? 10);

        $since = now()->subMinutes($sinceMinutes);

        try {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '>=', $since->toDateTimeString())
                ->count();
        } catch (\Throwable $e) {
            $this->warn('Unable to query failed_jobs table: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($count === 0) {
            $this->info("No failed jobs in the last {$sinceMinutes} minutes.");

            return self::SUCCESS;
        }

        $latest = DB::table('failed_jobs')
            ->where('failed_at', '>=', $since->toDateTimeString())
            ->orderByDesc('failed_at')
            ->first();

        $message = "[queue:monitor-failed] {$count} failed job(s) in the last {$sinceMinutes} min. Latest: {$latest->id} @ {$latest->failed_at} — {$latest->exception}";

        Log::error($message);

        $this->error($message);

        return self::SUCCESS;
    }
}
