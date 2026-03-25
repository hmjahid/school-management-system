<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test 
                            {user : The ID of the user to send the notification to}
                            {--type=test : The notification type}
                            {--channel=* : The channels to send the notification to (database,mail,sms,push)}
                            {--message= : Custom notification message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to a user';

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationDeliveryService
     */
    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\NotificationDeliveryService  $notificationService
     * @return void
     */
    public function __construct(NotificationDeliveryService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user');
        $type = $this->option('type');
        $channels = $this->option('channel') ?: ['database', 'mail', 'sms', 'push'];
        $message = $this->option('message') ?? 'This is a test notification';

        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Sending test notification to user: {$user->name} ({$user->email})");
        $this->info("Notification type: {$type}");
        $this->info("Channels: " . implode(', ', $channels));
        
        $data = [
            'message' => $message,
            'time' => now()->toDateTimeString(),
            'test' => true,
        ];

        try {
            $results = $this->notificationService->send($user, $type, $data, $channels);
            
            $this->info("\nNotification sent successfully!");
            $this->line('');
            
            $this->table(
                ['Channel', 'Status', 'Details'],
                $this->formatResults($results)
            );
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
            Log::error('Failed to send test notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
    
    /**
     * Format the results for display.
     *
     * @param  array  $results
     * @return array
     */
    protected function formatResults(array $results): array
    {
        $formatted = [];
        
        foreach ($results as $channel => $result) {
            $status = $result['success'] ? '<fg=green>✓ Success</>' : '<fg=red>✗ Failed</>';
            $details = json_encode($result['data'] ?? $result['error'] ?? 'No details');
            
            if (isset($result['error'])) {
                $details = "<fg=red>{$result['error']}</>" . PHP_EOL . $details;
            }
            
            $formatted[] = [
                $channel,
                $status,
                $details,
            ];
        }
        
        return $formatted;
    }
}
