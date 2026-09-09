<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SectionController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $classId = (int) ($_GET['class_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($classId > 0) {
            $where .= ' AND sec.class_id = ?';
            $params[] = $classId;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM sections sec WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT sec.*, c.name as class_name,
                (SELECT COUNT(*) FROM students s WHERE s.section_id = sec.id AND s.status = 'active') as student_count
             FROM sections sec
             LEFT JOIN school_classes c ON sec.class_id = c.id
             WHERE {$where}
             ORDER BY c.name ASC, sec.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");

        $this->view('dashboard.sections.index', [
            'rows'     => $rows,
            'sections' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'classes'  => $classes,
            'classId'  => $classId,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'      => 'required|max:100',
            'class_id'  => 'required|numeric',
            'capacity'  => 'numeric',
        ]);

        $this->db->insert('sections', [
            'name'       => $data['name'],
            'class_id'   => $data['class_id'],
            'capacity'   => $data['capacity'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Section created successfully.');
        $this->redirect('/dashboard/sections');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $section = $this->db->fetch("SELECT * FROM sections WHERE id = ? LIMIT 1", [$id]);
        if (!$section) {
            Session::getInstance()->flash('error', 'Section not found.');
            $this->redirect('/dashboard/sections');
            return;
        }

        $data = $this->validate([
            'name'      => 'required|max:100',
            'class_id'  => 'required|numeric',
            'capacity'  => 'numeric',
        ]);

        $this->db->update('sections', [
            'name'       => $data['name'],
            'class_id'   => $data['class_id'],
            'capacity'   => $data['capacity'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Section updated successfully.');
        $this->redirect('/dashboard/sections');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $studentCount = $this->db->count('students', "section_id = ? AND status = 'active'", [$id]);
        if ($studentCount > 0) {
            Session::getInstance()->flash('error', 'Cannot delete section with active students.');
            $this->redirect('/dashboard/sections');
            return;
        }

        $this->db->delete('sections', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Section removed.');
        $this->redirect('/dashboard/sections');
    }
}
