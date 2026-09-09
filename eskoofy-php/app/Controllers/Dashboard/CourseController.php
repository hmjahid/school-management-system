<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class CourseController extends Controller
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
            $where .= " AND (co.name LIKE ? OR co.code LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM courses co WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT co.*, sc.name as class_name,
                (SELECT COUNT(*) FROM course_subjects cs WHERE cs.course_id = co.id) as subject_count
             FROM courses co
             LEFT JOIN school_classes sc ON co.class_id = sc.id
             WHERE {$where}
             ORDER BY co.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.courses.index', [
            'rows'     => $rows,
            'courses' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'      => 'required|max:255',
            'code'      => 'required|max:50',
            'class_id'  => 'numeric',
            'description' => 'max:1000',
            'status'    => 'in:active,inactive',
        ]);

        $this->db->insert('courses', [
            'name'        => $data['name'],
            'code'        => $data['code'],
            'class_id'    => $data['class_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Course created.');
        $this->redirect('/dashboard/courses');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $course = $this->db->fetch("SELECT * FROM courses WHERE id = ? LIMIT 1", [$id]);
        if (!$course) {
            Session::getInstance()->flash('error', 'Course not found.');
            $this->redirect('/dashboard/courses');
            return;
        }

        $data = $this->validate([
            'name'      => 'required|max:255',
            'code'      => 'required|max:50',
            'class_id'  => 'numeric',
            'description' => 'max:1000',
            'status'    => 'in:active,inactive',
        ]);

        $this->db->update('courses', [
            'name'        => $data['name'],
            'code'        => $data['code'],
            'class_id'    => $data['class_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'active',
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Course updated.');
        $this->redirect('/dashboard/courses');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('courses', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Course deleted.');
        $this->redirect('/dashboard/courses');
    }
}
