<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class GuardianController extends Controller
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR g.relationship LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM guardians g LEFT JOIN users u ON g.user_id = u.id WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT g.*, u.name, u.email, u.phone,
                (SELECT COUNT(*) FROM students s WHERE s.guardian_id = g.id) as student_count
             FROM guardians g
             LEFT JOIN users u ON g.user_id = u.id
             WHERE {$where}
             ORDER BY g.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.guardians.index', [
            'rows'     => $rows,
            'guardians' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.guardians.create');
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'          => 'required|max:255',
            'email'         => 'required|email',
            'phone'         => 'max:20',
            'relationship'  => 'max:50',
            'occupation'    => 'max:100',
            'address'       => 'max:500',
            'national_id'   => 'max:50',
        ]);

        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
        if ($exists) {
            Session::getInstance()->flash('error', 'A user with this email already exists.');
            $this->back();
            return;
        }

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role'       => 'parent',
            'password'   => Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert('guardians', [
            'user_id'      => $userId,
            'relationship' => $data['relationship'] ?? null,
            'occupation'   => $data['occupation'] ?? null,
            'address'      => $data['address'] ?? null,
            'national_id'  => $data['national_id'] ?? null,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Guardian created successfully.');
        $this->redirect('/dashboard/guardians');
    }
}
