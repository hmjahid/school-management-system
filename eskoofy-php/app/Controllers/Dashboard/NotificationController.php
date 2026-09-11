<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

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

        $prefs = $this->db->fetch(
            "SELECT * FROM notification_preferences WHERE user_id = ? LIMIT 1",
            [$userId]
        );

        $this->view('dashboard.notifications.preferences', [
            'prefs' => $prefs,
        ]);
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

    public function markAllRead(): void
    {
        Auth::requireAuth();
        $this->db->update('notification_logs', [
            'status'      => 'delivered',
            'opened_at'   => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'notifiable_type = ? AND notifiable_id = ? AND opened_at IS NULL', ['App\\Models\\User', Auth::id()]);

        Session::getInstance()->flash('success', 'All notifications marked as read.');
        $this->redirect('/dashboard/notifications');
    }
}