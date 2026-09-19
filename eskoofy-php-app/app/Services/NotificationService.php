<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Notification dispatch — writes to notification_logs for in-app delivery and
 * hands off to the push service for device push (FCM) when the channel is set.
 * Parity with eskoofy-laravel-app NotificationService + LogPushNotificationService.
 */
class NotificationService
{
    private const NOTIFIABLE = 'App\\Models\\User';

    private DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Send to a list of recipient user ids.
     *
     * @param  array<int,int>  $recipientIds
     * @param  array<string,mixed>  $options
     */
    public function notifyMany(array $recipientIds, string $message, array $options = []): int
    {
        $count = 0;
        foreach ($recipientIds as $userId) {
            if ($this->notify((int) $userId, $message, $options)) {
                ++$count;
            }
        }
        return $count;
    }

    /**
     * @param  array<string,mixed>  $options  type, title, url, channels
     */
    public function notify(int $userId, string $message, array $options = []): bool
    {
        $type     = (string) ($options['type'] ?? 'Notification');
        $title    = (string) ($options['title'] ?? $type);
        $channels = (array) ($options['channels'] ?? ['database']);

        $this->db->insert('notification_logs', [
            'type'            => $type,
            'notifiable_type' => self::NOTIFIABLE,
            'notifiable_id'   => $userId,
            'content'         => $message,
            'channel'         => in_array('push', $channels, true) ? 'database+push' : 'database',
            'status'          => 'sent',
            'sent_at'         => date('Y-m-d H:i:s'),
            'metadata'        => json_encode([
                'title' => $title,
                'message' => $message,
                'url'    => $options['url'] ?? null,
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (in_array('push', $channels, true)) {
            $this->push($userId, $title, $message);
        }

        return true;
    }

    /**
     * Best-effort device push via the FCM service.
     */
    private function push(int $userId, string $title, string $body): void
    {
        try {
            $service = new \App\Services\Push\FirebasePushService();
            $service->sendToUser($userId, $title, $body);
        } catch (\Throwable) {
            // Push failures must never break notification persistence.
        }
    }
}