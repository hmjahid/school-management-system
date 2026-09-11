<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class TestimonialController extends Controller
{
    private DatabaseInterface $db;

    private const TYPES = [
        'behavior', 'academic_excellence', 'sports', 'arts', 'leadership',
        'community_service', 'attendance', 'discipline', 'creativity', 'overall',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (t.testimonial_number LIKE ? OR t.name LIKE ? OR EXISTS (SELECT 1 FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = t.student_id AND u.name LIKE ?))";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($type !== '') {
            $where .= ' AND t.testimonial_type = ?';
            $params[] = $type;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM testimonials t WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT t.*, u.name as student_name
             FROM testimonials t
             LEFT JOIN students s ON t.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE {$where}
             ORDER BY t.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.testimonials.index', [
            'testimonials' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Testimonial::class),
            'types'        => self::TYPES,
            'total'        => $total,
            'page'         => $page,
            'perPage'      => $perPage,
            'lastPage'     => max(1, (int) ceil($total / $perPage)),
            'search'       => $search,
            'type'         => $type,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );

        $this->view('dashboard.testimonials.create', [
            'students' => $students,
            'types'    => self::TYPES,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'       => 'required|numeric',
            'testimonial_type' => 'required',
            'name'             => 'required|max:255',
            'issue_date'       => 'required',
            'status'           => 'max:20',
            'body'             => 'max:5000',
            'author_name'      => 'max:255',
            'author_designation'=> 'max:255',
            'rating'           => 'numeric|min:1|max:5',
        ]);

        $this->storeOne(
            (int) $data['student_id'],
            $data['testimonial_type'],
            $data['name'],
            $data['issue_date'],
            $data['body'] ?? null,
            $data['status'] ?? 'draft',
            $data['author_name'] ?? null,
            $data['author_designation'] ?? null,
            isset($data['rating']) ? (int) $data['rating'] : 5
        );

        Session::getInstance()->flash('success', 'Testimonial created.');
        $this->redirect('/dashboard/testimonials');
    }

    public function storeOne(int $studentId, string $type, string $name, string $issueDate, ?string $body, string $status, ?string $authorName, ?string $authorDesignation, int $rating): int
    {
        $year = date('Y');
        $count = (int) ($this->db->fetch(
            "SELECT COUNT(*) as c FROM testimonials WHERE YEAR(created_at) = ?", [$year]
        )['c'] ?? 0);
        $number = sprintf('TEST-%s-%04d', $year, $count + 1);

        return $this->db->insert('testimonials', [
            'student_id'        => $studentId,
            'testimonial_type'  => in_array($type, self::TYPES, true) ? $type : 'overall',
            'testimonial_number'=> $number,
            'name'              => $name,
            'issue_date'        => $issueDate,
            'status'            => in_array($status, ['draft', 'issued', 'revoked'], true) ? $status : 'draft',
            'body'              => ($body !== null && $body !== '') ? json_encode([$body]) : null,
            'author_name'       => $authorName,
            'author_designation'=> $authorDesignation,
            'content'           => $body ?? $name,
            'rating'            => $rating,
            'is_visible'        => 1,
            'generated_by'      => Auth::id(),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $testimonial = $this->loadTestimonial($id);
        if (!$testimonial) {
            Session::getInstance()->flash('error', 'Testimonial not found.');
            $this->redirect('/dashboard/testimonials');
            return;
        }
        $this->view('dashboard.testimonials.show', ['testimonial' => $testimonial]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $testimonial = $this->loadTestimonial($id);
        if (!$testimonial) {
            Session::getInstance()->flash('error', 'Testimonial not found.');
            $this->redirect('/dashboard/testimonials');
            return;
        }
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );
        $this->view('dashboard.testimonials.edit', [
            'testimonial' => $testimonial,
            'students'    => $students,
            'types'       => self::TYPES,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $testimonial = $this->loadTestimonial($id);
        if (!$testimonial) {
            Session::getInstance()->flash('error', 'Testimonial not found.');
            $this->redirect('/dashboard/testimonials');
            return;
        }

        $data = $this->validate([
            'student_id'       => 'required|numeric',
            'testimonial_type' => 'required',
            'name'             => 'required|max:255',
            'issue_date'       => 'required',
            'status'           => 'required',
            'body'             => 'max:5000',
            'author_name'      => 'max:255',
            'author_designation'=> 'max:255',
            'rating'           => 'numeric|min:1|max:5',
        ]);

        $this->db->update('testimonials', [
            'student_id'        => (int) $data['student_id'],
            'testimonial_type'  => in_array($data['testimonial_type'], self::TYPES, true) ? $data['testimonial_type'] : 'overall',
            'name'              => $data['name'],
            'issue_date'        => $data['issue_date'],
            'status'            => in_array($data['status'], ['draft', 'issued', 'revoked'], true) ? $data['status'] : 'draft',
            'body'              => (($data['body'] ?? '') !== '') ? json_encode([$data['body']]) : null,
            'content'           => $data['body'] ?? $data['name'],
            'author_name'       => $data['author_name'] ?? null,
            'author_designation'=> $data['author_designation'] ?? null,
            'rating'            => isset($data['rating']) ? (int) $data['rating'] : 5,
            'updated_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Testimonial updated.');
        $this->redirect('/dashboard/testimonials');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('testimonials', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Testimonial deleted.');
        $this->redirect('/dashboard/testimonials');
    }

    public function print(int $id): void
    {
        Auth::requireAuth();
        $testimonial = $this->loadTestimonial($id);
        if (!$testimonial) {
            Session::getInstance()->flash('error', 'Testimonial not found.');
            $this->redirect('/dashboard/testimonials');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.testimonials.print', [
            'testimonial' => $testimonial,
            'settings'    => $settings,
        ]);
    }

    private function loadTestimonial(int $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT t.*, u.name as student_name, c.name as class_name, sec.name as section_name,
                    s.admission_number, s.roll_number
             FROM testimonials t
             LEFT JOIN students s ON t.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE t.id = ? LIMIT 1",
            [$id]
        );
        if (!$row) {
            return null;
        }
        $row['body'] = isset($row['body']) && $row['body'] !== '' ? json_decode((string) $row['body'], true) : null;
        $row['details'] = isset($row['details']) && $row['details'] !== '' ? json_decode((string) $row['details'], true) : [];
        return $row;
    }
}