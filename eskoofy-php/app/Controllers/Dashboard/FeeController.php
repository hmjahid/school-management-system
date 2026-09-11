<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class FeeController extends Controller
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
        $classId = (int) ($_GET['class_id'] ?? 0);
        $feeType = $_GET['fee_type'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND f.name LIKE ?";
            $params[] = "%{$search}%";
        }
        if ($classId > 0) {
            $where .= ' AND f.class_id = ?';
            $params[] = $classId;
        }
        if ($feeType !== '') {
            $where .= ' AND f.fee_type = ?';
            $params[] = $feeType;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM fees f WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT f.*, c.name as class_name, sec.name as section_name
             FROM fees f
             LEFT JOIN school_classes c ON f.class_id = c.id
             LEFT JOIN sections sec ON f.section_id = sec.id
             WHERE {$where}
             ORDER BY f.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.fees.index', [
            'rows'      => $rows,
            'fees'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'search'    => $search,
            'classId'   => $classId,
            'feeType'   => $feeType,
            'classes'   => $classes,
            'sections'  => $sections,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $students = $this->db->fetchAll(
            "SELECT s.id, u.name FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.status = 'active' ORDER BY u.name ASC LIMIT 200"
        );

        $this->view('dashboard.fees.create', [
            'classes'  => $classes,
            'sections' => $sections,
            'students' => $students,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'fee_type'    => 'required|max:50',
            'amount'      => 'required|numeric',
            'frequency'   => 'max:50',
            'class_id'    => 'numeric',
            'section_id'  => 'numeric',
            'student_id'  => 'numeric',
            'description' => 'max:1000',
            'status'      => 'in:active,inactive',
        ]);

        $this->db->insert('fees', [
            'name'        => $data['name'],
            'fee_type'    => $data['fee_type'],
            'amount'      => $data['amount'],
            'frequency'   => $data['frequency'] ?? null,
            'class_id'    => $data['class_id'] ?? null,
            'section_id'  => $data['section_id'] ?? null,
            'student_id'  => $data['student_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
            'created_by'  => Auth::id(),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Fee created successfully.');
        $this->redirect('/dashboard/fees');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $fee = $this->db->fetch("SELECT * FROM fees WHERE id = ? LIMIT 1", [$id]);
        if (!$fee) {
            Session::getInstance()->flash('error', 'Fee not found.');
            $this->redirect('/dashboard/fees');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'fee_type'    => 'required|max:50',
            'amount'      => 'required|numeric',
            'frequency'   => 'max:50',
            'class_id'    => 'numeric',
            'section_id'  => 'numeric',
            'student_id'  => 'numeric',
            'description' => 'max:1000',
            'status'      => 'in:active,inactive',
        ]);

        $this->db->update('fees', [
            'name'        => $data['name'],
            'fee_type'    => $data['fee_type'],
            'amount'      => $data['amount'],
            'frequency'   => $data['frequency'] ?? null,
            'class_id'    => $data['class_id'] ?? null,
            'section_id'  => $data['section_id'] ?? null,
            'student_id'  => $data['student_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Fee updated successfully.');
        $this->redirect('/dashboard/fees');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $hasPayments = $this->db->count('fee_payments', "fee_id = ?", [$id]);
        if ($hasPayments > 0) {
            Session::getInstance()->flash('error', 'Cannot delete fee with existing payments.');
            $this->redirect('/dashboard/fees');
            return;
        }

        $this->db->delete('fees', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Fee removed.');
        $this->redirect('/dashboard/fees');
    }
}
