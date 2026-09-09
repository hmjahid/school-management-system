<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class ActivityController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        $userId = $_GET['user_id'] ?? '';
        $action = $_GET['action'] ?? '';

        $where = '1=1';
        $params = [];
        if ($userId !== '') {
            $where .= ' AND al.user_id = ?';
            $params[] = (int) $userId;
        }
        if ($action !== '') {
            $where .= ' AND al.action = ?';
            $params[] = $action;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM activity_logs al WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT al.*, u.name as user_name
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE {$where}
             ORDER BY al.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC LIMIT 500");

        $this->view('dashboard.activity.index', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'users'    => $users,
            'userId'   => $userId,
            'action'   => $action,
        ]);
    }
}
