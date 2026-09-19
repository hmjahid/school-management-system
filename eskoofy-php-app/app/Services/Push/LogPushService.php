<?php
declare(strict_types=1);

namespace App\Services\Push;

use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Log-only push delivery — writes push attempts to the log channel. Used when
 * FCM credentials are not configured (mirrors app LogPushService).
 */
class LogPushService implements PushNotificationService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        $entry = sprintf(
            '[push:log] title=%s body=%s token=%s',
            $title,
            $body,
            substr($token, 0, 24)
        );
        error_log($entry);
        return ['success' => true, 'message_id' => 'log-' . md5($entry)];
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $db = Database::getInstance();
        $tokens = [];
        try {
            if ($db->hasTable('device_tokens')) {
                $tokens = $db->fetchAll(
                    "SELECT token FROM device_tokens WHERE user_id = ?",
                    [$userId]
                );
            }
        } catch (\Throwable) {
            $tokens = [];
        }
        foreach ($tokens as $row) {
            $this->sendToToken((string) $row['token'], $title, $body, $data);
        }
        return ['success' => true, 'sent' => count($tokens), 'failed' => 0];
    }
}