<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class NotificationController extends Controller
{
    private Database $db;

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

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ?", [$userId]
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ?
             ORDER BY is_read ASC, created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$userId]
        );

        $unreadCount = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]
        )['cnt'] ?? 0);

        $this->view('dashboard.notifications.index', [
            'rows'        => $rows,
            'notifications' => $rows,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
            'unreadCount' => $unreadCount,
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
        $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND user_id = ?', [$id, Auth::id()]);

        $this->redirect('/dashboard/notifications');
    }

    public function markAllRead(): void
    {
        Auth::requireAuth();
        $this->db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ], 'user_id = ? AND is_read = 0', [Auth::id()]);

        Session::getInstance()->flash('success', 'All notifications marked as read.');
        $this->redirect('/dashboard/notifications');
    }
}
