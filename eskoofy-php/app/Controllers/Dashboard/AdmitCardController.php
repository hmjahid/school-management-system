<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class AdmitCardController extends Controller
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
        $examId = (int) ($_GET['exam_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($examId > 0) {
            $where .= ' AND ac.exam_id = ?';
            $params[] = $examId;
        }
        if ($search !== '') {
            $where .= " AND (ac.admit_card_number LIKE ? OR EXISTS (SELECT 1 FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ac.student_id AND u.name LIKE ?))";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM admit_cards ac WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT ac.*, u.name as student_name, s.admission_number, e.name as exam_name, c.name as class_name
             FROM admit_cards ac
             LEFT JOIN students s ON ac.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN exams e ON ac.exam_id = e.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             WHERE {$where}
             ORDER BY ac.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $exams = $this->db->fetchAll("SELECT id, name FROM exams ORDER BY id DESC LIMIT 50");

        $this->view('dashboard.admit-cards.index', [
            'rows'     => $rows,
            'exams'    => $exams,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'examId'   => $examId,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $exams = $this->db->fetchAll("SELECT id, name FROM exams ORDER BY id DESC LIMIT 50");
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id
             ORDER BY s.id DESC LIMIT 500"
        );

        $this->view('dashboard.admit-cards.create', [
            'exams'    => $exams,
            'students' => $students,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'exam_id'   => 'required|numeric',
            'student_id'=> 'required|numeric',
            'issue_date'=> 'required',
        ]);

        $this->storeOne((int) $data['exam_id'], (int) $data['student_id'], $data['issue_date']);

        Session::getInstance()->flash('success', 'Admit card generated.');
        $this->redirect('/dashboard/admit-cards');
    }

    public function generate(): void
    {
        $this->store();
    }

    public function storeOne(int $examId, int $studentId, string $issueDate): bool
    {
        $exists = $this->db->fetch(
            "SELECT id FROM admit_cards WHERE exam_id = ? AND student_id = ? LIMIT 1",
            [$examId, $studentId]
        );
        if ($exists) {
            return false;
        }

        $count = (int) ($this->db->fetch("SELECT COUNT(*) as c FROM admit_cards WHERE deleted_at IS NULL")['c'] ?? 0);
        $number = sprintf('ADMIT-%d-%d-%04d', $examId, $studentId, $count + 1);

        $this->db->insert('admit_cards', [
            'exam_id'          => $examId,
            'student_id'       => $studentId,
            'admit_card_number'=> $number,
            'issue_date'       => $issueDate,
            'status'           => 'issued',
            'generated_by'     => Auth::id(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function batchCreate(): void
    {
        Auth::requireAuth();
        $exams = $this->db->fetchAll("SELECT id, name FROM exams ORDER BY id DESC LIMIT 100");
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.admit-cards.batch', [
            'exams'    => $exams,
            'classes'  => $classes,
            'sections' => $sections,
        ]);
    }

    public function batchStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'exam_id'   => 'required|numeric',
            'class_id'  => 'required|numeric',
            'issue_date'=> 'required',
            'section_id'=> 'numeric',
        ]);

        $examId = (int) $data['exam_id'];
        $classId = (int) $data['class_id'];
        $sectionId = isset($data['section_id']) ? (int) $data['section_id'] : 0;
        $issueDate = $data['issue_date'];

        $where = 's.class_id = ?';
        $params = [$classId];
        if ($sectionId > 0) {
            $where .= ' AND s.section_id = ?';
            $params[] = $sectionId;
        }
        $students = $this->db->fetchAll(
            "SELECT s.id FROM students s JOIN users u ON s.user_id = u.id WHERE {$where}",
            $params
        );

        $count = 0;
        foreach ($students as $student) {
            if ($this->storeOne($examId, (int) $student['id'], $issueDate)) {
                $count++;
            }
        }

        Session::getInstance()->flash('success', "{$count} admit cards generated.");
        $this->redirect('/dashboard/admit-cards');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'Admit card not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }

        $this->view('dashboard.admit-cards.show', ['card' => $card]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'Admit card not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }

        $exams = $this->db->fetchAll("SELECT id, name FROM exams ORDER BY id DESC LIMIT 50");
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );

        $this->view('dashboard.admit-cards.edit', [
            'card'     => $card,
            'exams'    => $exams,
            'students' => $students,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'Admit card not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }

        $data = $this->validate([
            'exam_id'    => 'required|numeric',
            'student_id' => 'required|numeric',
            'issue_date' => 'required',
            'status'     => 'required',
        ]);

        $count = (int) ($this->db->fetch("SELECT COUNT(*) as c FROM admit_cards WHERE deleted_at IS NULL")['c'] ?? 0);
        $number = sprintf('ADMIT-%d-%d-%04d', (int) $data['exam_id'], (int) $data['student_id'], $count + 1);

        $this->db->update('admit_cards', [
            'exam_id'          => (int) $data['exam_id'],
            'student_id'       => (int) $data['student_id'],
            'issue_date'       => $data['issue_date'],
            'status'           => in_array($data['status'], ['issued', 'revoked'], true) ? $data['status'] : 'issued',
            'admit_card_number'=> $number,
            'updated_at'       => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Admit card updated.');
        $this->redirect("/dashboard/admit-cards/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->update('admit_cards', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Admit card removed.');
        $this->redirect('/dashboard/admit-cards');
    }

    public function print(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'Admit card not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $preview = (bool) ($_GET['preview'] ?? false);
        $this->view('dashboard.admit-cards.print', [
            'card'     => $card,
            'settings' => $settings,
            'preview'  => $preview,
        ]);
    }

    public function preview(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'Admit card not found.');
            $this->redirect('/dashboard/admit-cards');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.admit-cards.print', [
            'card'     => $card,
            'settings' => $settings,
            'preview'  => true,
        ]);
    }

    private function loadCard(int $id): ?array
    {
        $card = $this->db->fetch(
            "SELECT ac.*, u.name as student_name, s.admission_number, s.roll_number, s.gender,
                    e.name as exam_name, e.start_date, c.name as class_name, sec.name as section_name
             FROM admit_cards ac
             LEFT JOIN students s ON ac.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN exams e ON ac.exam_id = e.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE ac.id = ? AND ac.deleted_at IS NULL LIMIT 1",
            [$id]
        );
        if (!$card) {
            return null;
        }
        $card['details'] = isset($card['details']) && $card['details'] !== '' ? json_decode((string) $card['details'], true) : [];
        return $card;
    }
}