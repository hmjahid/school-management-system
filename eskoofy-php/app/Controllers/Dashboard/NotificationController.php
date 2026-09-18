<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Models\NotificationPreference;

class NotificationController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $notifiable = 'App\\Models\\User';

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notification_logs WHERE notifiable_type = ? AND notifiable_id = ?",
            [$notifiable, $userId]
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT * FROM notification_logs
             WHERE notifiable_type = ? AND notifiable_id = ?
             ORDER BY opened_at IS NULL DESC, created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$notifiable, $userId]
        );

        $unreadCount = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notification_logs
             WHERE notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL",
            [$notifiable, $userId]
        )['cnt'] ?? 0);

        $this->view('dashboard.notifications.index', [
            'rows'          => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\NotificationLog::class),
            'notifications' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\NotificationLog::class),
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
            'lastPage'      => max(1, (int) ceil($total / $perPage)),
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function preferences(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $this->view('dashboard.notifications.preferences', [
            'preferences' => NotificationPreference::getUserPreferences($userId),
            'types'       => NotificationPreference::getAvailableTypes(),
            'channels'    => NotificationPreference::getAvailableChannels(),
        ]);
    }

    public function updatePreferences(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $raw = $_POST['preferences'] ?? null;
        if (!is_array($raw)) {
            Session::getInstance()->flash('error', 'No preferences submitted.');
            $this->back();
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
        Session::getInstance()->flash('success', __('Preferences saved.'));
        $this->back();
    }

    public function markRead(int $id): void
    {
        Auth::requireAuth();
        $this->db->update('notification_logs', [
            'status'      => 'delivered',
            'opened_at'   => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ? AND notifiable_type = ? AND notifiable_id = ?', [$id, 'App\\Models\\User', Auth::id()]);

        $this->redirect('/dashboard/notifications');
    }

    public function list(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        $notifiable = 'App\\Models\\User';

        $rows = $this->db->fetchAll(
            "SELECT * FROM notification_logs
             WHERE notifiable_type = ? AND notifiable_id = ?
             ORDER BY created_at DESC
             LIMIT 15",
            [$notifiable, $userId]
        );

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
                'unread'     => ($n['opened_at'] ?? null) === null,
                'created_at' => $n['created_at'] ?? null,
            ];
        }

        $unreadCount = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notification_logs
             WHERE notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL",
            [$notifiable, $userId]
        )['cnt'] ?? 0);

        $this->json([
            'items'        => $items,
            'unread_count' => $unreadCount,
            'csrf'         => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    public function markAllRead(): void
    {
        Auth::requireAuth();
        $this->db->update('notification_logs', [
            'status'      => 'delivered',
            'opened_at'   => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL', ['App\\Models\\User', Auth::id()]);

        $this->json(['ok' => true, 'unread_count' => 0]);
    }

    public function templates(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll("SELECT * FROM notification_templates ORDER BY name ASC");
        $this->view('dashboard.notifications.templates', ['templates' => $rows]);
    }

    public function saveTemplate(): void
    {
        Auth::requireAuth();
        $key = strtolower((string) preg_replace('/[^a-z0-9._-]/i', '-', trim((string) ($_POST['key'] ?? ''))));
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || $key === '') {
            Session::getInstance()->flash('error', 'Name and key are required.');
            $this->back();
            return;
        }

        $exists = $this->db->fetch("SELECT id FROM notification_templates WHERE `key` = ?", [$key]);
        if ($exists) {
            Session::getInstance()->flash('error', 'A template with that key already exists.');
            $this->back();
            return;
        }

        $this->db->insert('notification_templates', [
            'name'            => $name,
            'key'             => $key,
            'subject'         => trim((string) ($_POST['subject'] ?? '')),
            'content'         => (string) ($_POST['content'] ?? ''),
            'sms_content'     => (string) ($_POST['sms_content'] ?? ''),
            'in_app_content'  => (string) ($_POST['in_app_content'] ?? ''),
            'variables'       => '[]',
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Notification template created.');
        $this->back();
    }

    public function updateTemplate(int $id): void
    {
        Auth::requireAuth();
        $key = strtolower((string) preg_replace('/[^a-z0-9._-]/i', '-', trim((string) ($_POST['key'] ?? ''))));
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || $key === '') {
            Session::getInstance()->flash('error', 'Name and key are required.');
            $this->back();
            return;
        }

        $dupe = $this->db->fetch("SELECT id FROM notification_templates WHERE `key` = ? AND id != ?", [$key, $id]);
        if ($dupe) {
            Session::getInstance()->flash('error', 'A template with that key already exists.');
            $this->back();
            return;
        }

        $this->db->update('notification_templates', [
            'name'            => $name,
            'key'             => $key,
            'subject'         => trim((string) ($_POST['subject'] ?? '')),
            'content'         => (string) ($_POST['content'] ?? ''),
            'sms_content'     => (string) ($_POST['sms_content'] ?? ''),
            'in_app_content'  => (string) ($_POST['in_app_content'] ?? ''),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Notification template updated.');
        $this->back();
    }

    public function deleteTemplate(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('notification_templates', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Notification template deleted.');
        $this->back();
    }
}