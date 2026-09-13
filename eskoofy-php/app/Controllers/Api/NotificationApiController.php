<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\NotificationPreference;

class NotificationApiController extends Controller
{
    private const NOTIFIABLE = 'App\\Models\\User';

    private function db(): \App\Core\DatabaseInterface
    {
        return Database::getInstance();
    }

    private function items(array $rows): array
    {
        $items = [];
        foreach ($rows as $n) {
            $data = json_decode((string) ($n['metadata'] ?? '[]'), true) ?: [];
            if ($data === [] && !empty($n['content'])) {
                $data = ['message' => $n['content']];
            }
            $type = $n['type'] ?? 'Notification';
            $items[] = [
                'id'         => $n['id'],
                'type'       => class_basename((string) $type),
                'title'      => $data['title'] ?? class_basename((string) $type),
                'message'    => $data['message'] ?? $data['body'] ?? '',
                'url'        => $data['url'] ?? null,
                'read_at'    => $n['opened_at'] ?? null,
                'unread'     => ($n['opened_at'] ?? null) === null,
                'created_at' => $n['created_at'] ?? null,
            ];
        }
        return $items;
    }

    private function countUnread(int $userId): int
    {
        return (int) ($this->db()->fetch(
            "SELECT COUNT(*) as cnt FROM notification_logs
             WHERE notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL",
            [self::NOTIFIABLE, $userId]
        )['cnt'] ?? 0);
    }

    public function index(): void
    {
        $userId = (int) Auth::id();
        $limit = max(1, (int) ($_GET['limit'] ?? 15));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $unreadOnly = !empty($_GET['unread_only']);

        $where = 'notifiable_type = ? AND notifiable_id = ?';
        $params = [self::NOTIFIABLE, $userId];
        if ($unreadOnly) {
            $where .= ' AND opened_at IS NULL';
        }

        $total = (int) ($this->db()->fetch(
            "SELECT COUNT(*) as cnt FROM notification_logs WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db()->fetchAll(
            "SELECT * FROM notification_logs
             WHERE {$where}
             ORDER BY created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $this->success([
            'data' => $this->items($rows),
            'meta' => [
                'total'        => count($rows),
                'unread_count' => $this->countUnread($userId),
                'has_more'     => count($rows) >= $limit,
            ],
        ], 'Notifications retrieved');
    }

    public function unreadCount(): void
    {
        $this->success(['unread_count' => $this->countUnread((int) Auth::id())], 'Unread count retrieved');
    }

    public function markAsRead(int $id): void
    {
        $userId = (int) Auth::id();
        $updated = $this->db()->update(
            'notification_logs',
            [
                'status'     => 'delivered',
                'opened_at'  => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'id = ? AND notifiable_type = ? AND notifiable_id = ?',
            [$id, self::NOTIFIABLE, $userId]
        );

        if (!$updated) {
            $this->error('Notification not found or already read.', 404);
            return;
        }

        $this->success([
            'message'      => 'Notification marked as read.',
            'unread_count' => $this->countUnread($userId),
        ], 'Notification marked as read');
    }

    public function markAllAsRead(): void
    {
        $userId = (int) Auth::id();
        $count = (int) $this->db()->update(
            'notification_logs',
            [
                'status'     => 'delivered',
                'opened_at'  => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL',
            [self::NOTIFIABLE, $userId]
        );

        $this->success([
            'message'      => $count . ' notifications marked as read.',
            'unread_count' => 0,
        ], 'Notifications marked as read');
    }

    public function destroy(int $id): void
    {
        $userId = (int) Auth::id();
        $deleted = $this->db()->delete(
            'notification_logs',
            'id = ? AND notifiable_type = ? AND notifiable_id = ?',
            [$id, self::NOTIFIABLE, $userId]
        );

        if (!$deleted) {
            $this->error('Notification not found.', 404);
            return;
        }

        $this->success([
            'message'      => 'Notification deleted successfully.',
            'unread_count' => $this->countUnread($userId),
        ], 'Notification deleted');
    }

    public function clearAll(): void
    {
        $userId = (int) Auth::id();
        $count = (int) $this->db()->delete(
            'notification_logs',
            'notifiable_type = ? AND notifiable_id = ?',
            [self::NOTIFIABLE, $userId]
        );

        $this->success([
            'message'      => $count . ' notifications cleared.',
            'unread_count' => 0,
        ], 'Notifications cleared');
    }

    public function getPreferences(): void
    {
        $this->success([
            'data' => NotificationPreference::getUserPreferences((int) Auth::id()),
        ], 'Notification preferences retrieved');
    }

    public function updatePreferences(): void
    {
        $userId = (int) Auth::id();

        $raw = $_POST['preferences'] ?? null;
        if (!is_array($raw)) {
            $this->error('The preferences field is required.', 422);
            return;
        }

        $parsed = [];
        foreach ($raw as $type => $channels) {
            if (!NotificationPreference::isValidType((string) $type) || !is_array($channels)) {
                continue;
            }
            $parsed[$type] = array_map(
                static fn ($v) => (bool) $v,
                $channels
            );
        }

        NotificationPreference::setUserPreferences($userId, $parsed);

        $this->success([
            'message' => 'Notification preferences updated successfully.',
            'data'    => NotificationPreference::getUserPreferences($userId),
        ], 'Notification preferences updated');
    }

    public function stream(): void
    {
        $this->success(['message' => 'WebSocket/SSE connection established.']);
    }
}