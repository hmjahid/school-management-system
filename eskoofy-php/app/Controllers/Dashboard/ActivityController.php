<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

class ActivityController extends Controller
{
    private DatabaseInterface $db;

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
            $where .= " AND al.causer_type = 'App\\Models\\User' AND al.causer_id = ?";
            $params[] = (int) $userId;
        }
        if ($action !== '') {
            $where .= ' AND al.event = ?';
            $params[] = $action;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM activity_log al WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT al.*, u.name as user_name
             FROM activity_log al
             LEFT JOIN users u ON al.causer_type = 'App\\Models\\User' AND al.causer_id = u.id
             WHERE {$where}
             ORDER BY al.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC LIMIT 500");

        $this->view('dashboard.activity.index', [
            'rows'     => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Activity::class),
            'activity' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Activity::class),
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
