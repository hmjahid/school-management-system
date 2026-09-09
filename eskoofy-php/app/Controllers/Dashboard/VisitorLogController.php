<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

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
        $date = $_GET['date'] ?? date('Y-m-d');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "vl.visit_date = ?";
        $params = [$date];
        if ($search !== '') {
            $where .= " AND (vl.visitor_name LIKE ? OR vl.purpose LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM visitor_logs vl WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT vl.*, u.name as logged_by_name
             FROM visitor_logs vl
             LEFT JOIN users u ON vl.logged_by = u.id
             WHERE {$where}
             ORDER BY vl.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.visitor_logs.index', [
            'rows'     => $rows,
            'visitor_logs' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'date'     => $date,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'visitor_name'    => 'required|max:255',
            'phone'           => 'max:20',
            'id_number'       => 'max:100',
            'purpose'         => 'required|max:255',
            'person_to_meet'  => 'max:255',
            'vehicle_number'  => 'max:50',
            'visit_date'      => 'required',
            'check_in_time'   => 'max:20',
            'check_out_time'  => 'max:20',
            'notes'           => 'max:1000',
        ]);

        $this->db->insert('visitor_logs', [
            'visitor_name'   => $data['visitor_name'],
            'phone'          => $data['phone'] ?? null,
            'id_number'      => $data['id_number'] ?? null,
            'purpose'        => $data['purpose'],
            'person_to_meet' => $data['person_to_meet'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'visit_date'     => $data['visit_date'],
            'check_in_time'  => $data['check_in_time'] ?? date('H:i:s'),
            'check_out_time' => $data['check_out_time'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'logged_by'      => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Visitor logged.');
        $this->redirect('/dashboard/visitor-logs');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('visitor_logs', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Visitor log deleted.');
        $this->redirect('/dashboard/visitor-logs');
    }
}
