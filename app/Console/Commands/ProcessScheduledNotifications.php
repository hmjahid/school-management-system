<?php

namespace App\Console\Commands;

use App\Services\Notification\ScheduledNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-scheduled {--limit=10 : Maximum number of notifications to process in one batch} {--force : Process even if not in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due scheduled notifications';

    /**
     * The scheduled notification service.
     *
     * @var \App\Services\Notification\ScheduledNotificationService
     */
    protected $scheduledNotificationService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\Notification\ScheduledNotificationService  $scheduledNotificationService
     * @return void
     */
    public function __construct(ScheduledNotificationService $scheduledNotificationService)
    {
        parent::__construct();
        $this->scheduledNotificationService = $scheduledNotificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->option('force') && $this->laravel->environment('production') === false) {
            $this->warn('This command is running in a non-production environment. Use --force to run anyway.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        
        $this->info("Processing up to {$limit} scheduled notifications...");
        
        $startTime = microtime(true);
        $processed = $this->scheduledNotificationService->processDueNotifications($limit);
        $elapsedTime = round((microtime(true) - $startTime) * 1000);
        
        $this->info("Processed {$processed} scheduled notifications in {$elapsedTime}ms");
        
        // Log the execution
        Log::info('Processed scheduled notifications', [
            'processed' => $processed,
            'elapsed_ms' => $elapsedTime,
            'batch_size' => $limit,
        ]);
        
        return 0;
    }
}
