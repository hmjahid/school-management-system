<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class GuardianController extends Controller
{
    private DatabaseInterface $db;

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
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id WHERE s.status = 'active' ORDER BY u.name ASC LIMIT 500"
        );
        $this->view('dashboard.guardians.create', ['students' => $students]);
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
            'present_address' => 'max:500',
            'nid_number'    => 'max:50',
            'student_ids'   => 'array',
        ]);

        $result = $this->saveGuardian($data, $_POST['student_ids'] ?? []);
        if ($result === 0) {
            Session::getInstance()->flash('error', 'A user with this email already exists.');
            $this->back();
            return;
        }

        Session::getInstance()->flash('success', 'Guardian created successfully.');
        $this->redirect('/dashboard/guardians');
    }

    public function saveGuardian(array $data, array $studentIds): int
    {
        $exists = $this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
        if ($exists) {
            return 0;
        }

        $userId = $this->db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'role'       => 'guardian',
            'role_id'     => \App\Core\Auth::roleId('guardian'),
            'password'   => Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $guardianId = $this->db->insert('guardians', [
            'user_id'         => $userId,
            'relation_type'   => $data['relationship'] ?? 'parent',
            'relationship'    => $data['relationship'] ?? null,
            'occupation'      => $data['occupation'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'present_address' => $data['present_address'] ?? null,
            'nid_number'      => $data['nid_number'] ?? null,
            'nationality'     => 'Bangladeshi',
            'country'         => 'Bangladesh',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        foreach ($studentIds as $studentId) {
            $this->db->insert('guardian_student', [
                'guardian_id' => $guardianId,
                'student_id'  => (int) $studentId,
                'relationship'=> $data['relationship'] ?? 'parent',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $guardianId;
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $guardian = $this->loadGuardian($id);
        if (!$guardian) {
            Session::getInstance()->flash('error', 'Guardian not found.');
            $this->redirect('/dashboard/guardians');
            return;
        }
        $this->view('dashboard.guardians.show', ['guardian' => $guardian]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $guardian = $this->loadGuardian($id);
        if (!$guardian) {
            Session::getInstance()->flash('error', 'Guardian not found.');
            $this->redirect('/dashboard/guardians');
            return;
        }
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id WHERE s.status = 'active' ORDER BY u.name ASC LIMIT 500"
        );
        $this->view('dashboard.guardians.edit', [
            'guardian' => $guardian,
            'students' => $students,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $guardian = $this->loadGuardian($id);
        if (!$guardian) {
            Session::getInstance()->flash('error', 'Guardian not found.');
            $this->redirect('/dashboard/guardians');
            return;
        }

        $data = $this->validate([
            'name'          => 'required|max:255',
            'email'         => 'required|email',
            'phone'         => 'max:20',
            'relationship'  => 'max:50',
            'occupation'    => 'max:100',
            'present_address' => 'max:500',
            'nid_number'    => 'max:50',
            'student_ids'   => 'array',
        ]);

        $this->db->update('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$guardian['user_id']]);

        $this->db->update('guardians', [
            'relation_type'   => $data['relationship'] ?? 'parent',
            'relationship'    => $data['relationship'] ?? null,
            'occupation'      => $data['occupation'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'present_address' => $data['present_address'] ?? null,
            'nid_number'      => $data['nid_number'] ?? null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        // Sync student associations.
        $this->db->delete('guardian_student', 'guardian_id = ?', [$id]);
        $studentIds = $_POST['student_ids'] ?? [];
        foreach ($studentIds as $studentId) {
            $this->db->insert('guardian_student', [
                'guardian_id' => $id,
                'student_id'  => (int) $studentId,
                'relationship'=> $data['relationship'] ?? 'parent',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        Session::getInstance()->flash('success', 'Guardian updated.');
        $this->redirect('/dashboard/guardians/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $guardian = $this->loadGuardian($id);
        if (!$guardian) {
            Session::getInstance()->flash('error', 'Guardian not found.');
            $this->redirect('/dashboard/guardians');
            return;
        }

        $studentCount = $this->db->count('guardian_student', 'guardian_id = ?', [$id]);
        if ($studentCount > 0) {
            Session::getInstance()->flash('error', 'Cannot delete guardian with student associations.');
            $this->redirect('/dashboard/guardians/' . $id);
            return;
        }

        $this->db->update('guardians', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        $this->db->update('users', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$guardian['user_id']]);

        Session::getInstance()->flash('success', 'Guardian removed.');
        $this->redirect('/dashboard/guardians');
    }

    private function loadGuardian(int $id): ?array
    {
        $guardian = $this->db->fetch(
            "SELECT g.*, u.name, u.email, u.phone
             FROM guardians g LEFT JOIN users u ON g.user_id = u.id
             WHERE g.id = ? AND g.deleted_at IS NULL LIMIT 1",
            [$id]
        );
        if (!$guardian) {
            return null;
        }
        $guardian['students'] = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name as student_name, c.name as class_name, gs.relationship
             FROM guardian_student gs
             LEFT JOIN students s ON gs.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE gs.guardian_id = ?
             ORDER BY u.name ASC",
            [$id]
        );
        return $guardian;
    }
}
