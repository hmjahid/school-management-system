<?php

namespace App\Services\Notification;

use App\Models\ScheduledNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduledNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Schedule a new notification.
     */
    public function schedule(
        string $name,
        string $type,
        array $channels,
        array $recipients,
        array $data,
        array $schedule,
        ?int $createdBy = null
    ): ScheduledNotification {
        $scheduledAt = $this->calculateScheduledAt($schedule);

        return ScheduledNotification::create([
            'name' => $name,
            'type' => $type,
            'channels' => $channels,
            'recipients' => $recipients,
            'data' => $data,
            'schedule' => $schedule,
            'scheduled_at' => $scheduledAt,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Process due scheduled notifications.
     */
    public function processDueNotifications(int $limit = 10): int
    {
        $processed = 0;
        $now = now();

        ScheduledNotification::due()
            ->where('scheduled_at', '<=', $now)
            ->take($limit)
            ->get()
            ->each(function ($notification) use (&$processed) {
                try {
                    if ($notification->markAsProcessing()) {
                        $this->processNotification($notification);
                        $processed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process scheduled notification: ' . $e->getMessage(), [
                        'notification_id' => $notification->id,
                        'exception' => $e
                    ]);
                    
                    $notification->markAsFailed($e->getMessage());
                }
            });

        return $processed;
    }

    /**
     * Process a single scheduled notification.
     */
    protected function processNotification(ScheduledNotification $notification): void
    {
        try {
            // Send the notification
            $result = $this->notificationService->send(
                $notification->type,
                $notification->recipients,
                $notification->data,
                $notification->channels
            );

            if ($result) {
                $notification->markAsSent();
                
                // If it's a recurring notification, schedule the next one
                if ($notification->schedule['type'] !== 'once') {
                    $notification->rescheduleForNextOccurrence();
                }
            } else {
                $notification->markAsFailed('Failed to send notification');
            }
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate the scheduled_at timestamp based on the schedule configuration.
     */
    protected function calculateScheduledAt(array $schedule): Carbon
    {
        $now = now();
        
        if (isset($schedule['datetime'])) {
            $scheduledAt = Carbon::parse($schedule['datetime'], $schedule['timezone'] ?? 'UTC');
            
            // If the scheduled time is in the past and it's a one-time notification, throw an exception
            if ($schedule['type'] === 'once' && $scheduledAt->isPast()) {
                throw new \InvalidArgumentException('Scheduled time must be in the future for one-time notifications');
            }
            
            return $scheduledAt;
        }
        
        // For recurring notifications without a specific datetime, schedule for the next interval
        switch ($schedule['type']) {
            case 'daily':
                return $now->addDay();
            case 'weekly':
                return $now->addWeek();
            case 'monthly':
                return $now->addMonth();
            case 'custom':
                return $now->add(
                    $schedule['interval'] ?? 1,
                    $schedule['unit'] ?? 'day'
                );
            default:
                throw new \InvalidArgumentException('Invalid schedule type');
        }
    }

    /**
     * Get upcoming scheduled notifications.
     */
    public function getUpcoming(int $limit = 10)
    {
        return ScheduledNotification::active()
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->take($limit)
            ->get();
    }

    /**
     * Cancel a scheduled notification.
     */
    public function cancel(int $id, int $userId = null): bool
    {
        $query = ScheduledNotification::where('id', $id);
        
        if ($userId) {
            $query->where('created_by', $userId);
        }
        
        $notification = $query->firstOrFail();
        
        return $notification->cancel();
    }

    /**
     * Get notification statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => ScheduledNotification::count(),
            'pending' => ScheduledNotification::where('status', 'pending')->count(),
            'sent' => ScheduledNotification::where('status', 'sent')->count(),
            'failed' => ScheduledNotification::where('status', 'failed')->count(),
            'cancelled' => ScheduledNotification::where('status', 'cancelled')->count(),
        ];
    }
}
