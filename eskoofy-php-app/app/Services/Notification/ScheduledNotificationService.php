<?php
declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Services\NotificationService;

/**
 * Scheduled notification processing — pulls due rows from scheduled_notifications
 * and fans them out to notification_logs via NotificationService.
 * Parity with eskoofy-laravel-app Notification\ScheduledNotificationService.
 */
class ScheduledNotificationService
{
    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function processDueNotifications(int $limit = 10): int
    {
        try {
            if (! $this->db->hasTable('scheduled_notifications')) {
                return 0;
            }
        } catch (\Throwable) {
            return 0;
        }

        $rows = $this->db->fetchAll(
            "SELECT * FROM scheduled_notifications
             WHERE status = 'pending' AND scheduled_at <= NOW() AND deleted_at IS NULL
             ORDER BY scheduled_at ASC LIMIT {$limit}"
        );

        $processed = 0;
        foreach ($rows as $row) {
            $this->sendOne($row);
            ++$processed;
        }
        return $processed;
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function sendOne(array $row): void
    {
        $service = new NotificationService($this->db);
        $recipients = json_decode((string) ($row['recipients'] ?? '[]'), true) ?: [];
        $channels   = json_decode((string) ($row['channels'] ?? '["database"]'), true) ?: ['database'];
        $data       = json_decode((string) ($row['data'] ?? '{}'), true) ?: [];
        $content    = is_array($data) && isset($data['message']) ? (string) $data['message'] : (string) json_encode($data);

        $ok = true;
        $error = null;
        try {
            $service->notifyMany($recipients, $content, [
                'type'    => (string) ($row['type'] ?? 'scheduled'),
                'channels' => $channels,
            ]);
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        $this->db->update('scheduled_notifications', [
            'status'        => $ok ? 'sent' : 'failed',
            'sent_at'       => $ok ? date('Y-m-d H:i:s') : null,
            'error_message' => $error,
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $row['id']]);
    }
}