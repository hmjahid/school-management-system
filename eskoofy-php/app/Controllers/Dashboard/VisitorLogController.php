<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class VisitorLogController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $like = "%{$search}%";
            $where .= " AND (vl.url LIKE ? OR vl.ip LIKE ? OR u.name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($dateFrom !== '') {
            $where .= ' AND DATE(vl.created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where .= ' AND DATE(vl.created_at) <= ?';
            $params[] = $dateTo;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM visitor_logs vl LEFT JOIN users u ON vl.user_id = u.id WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT vl.*, u.name as user_name
             FROM visitor_logs vl
             LEFT JOIN users u ON vl.user_id = u.id
             WHERE {$where}
             ORDER BY vl.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $stats = $this->db->fetch(
            "SELECT COUNT(*) as total, COUNT(DISTINCT ip) as unique_visitors,
                    SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as authenticated
             FROM visitor_logs"
        );

        $this->view('dashboard.visitor_logs.index', [
            'rows'           => $rows,
            'visitor_logs'   => $rows,
            'total'          => $total,
            'page'           => $page,
            'perPage'        => $perPage,
            'lastPage'       => max(1, (int) ceil($total / $perPage)),
            'search'         => $search,
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'totalVisits'    => (int) ($stats['total'] ?? 0),
            'uniqueVisitors' => (int) ($stats['unique_visitors'] ?? 0),
            'todayVisits'    => (int) ($stats['today'] ?? 0),
            'authenticatedVisits' => (int) ($stats['authenticated'] ?? 0),
        ]);
    }
}