<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class StudentIdCardController extends Controller
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
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (ic.id_card_number LIKE ? OR EXISTS (SELECT 1 FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ic.student_id AND u.name LIKE ?))";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM student_id_cards ic WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT ic.*, u.name as student_name, s.admission_number, c.name as class_name, sec.name as section_name
             FROM student_id_cards ic
             LEFT JOIN students s ON ic.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE {$where}
             ORDER BY ic.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.id-cards.index', [
            'rows'     => $rows,
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
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );

        $this->view('dashboard.id-cards.create', ['students' => $students]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'  => 'required|numeric',
            'issue_date'  => 'required',
            'expiry_date' => 'max:20',
            'blood_group' => 'max:10',
        ]);

        $this->storeOne((int) $data['student_id'], $data['issue_date'], $data['expiry_date'] ?? null, $data['blood_group'] ?? null);

        Session::getInstance()->flash('success', 'ID card generated.');
        $this->redirect('/dashboard/id-cards');
    }

    public function generate(): void
    {
        $this->store();
    }

    public function storeOne(int $studentId, string $issueDate, ?string $expiryDate, ?string $bloodGroup, ?string $photoUrl = null, ?array $details = null): bool
    {
        $exists = $this->db->fetch(
            "SELECT id FROM student_id_cards WHERE student_id = ? AND deleted_at IS NULL LIMIT 1",
            [$studentId]
        );
        if ($exists) {
            return false;
        }

        $count = (int) ($this->db->fetch("SELECT COUNT(*) as c FROM student_id_cards WHERE deleted_at IS NULL")['c'] ?? 0);
        $number = sprintf('ID-%d-%04d', $studentId, $count + 1);

        $this->db->insert('student_id_cards', [
            'student_id'     => $studentId,
            'id_card_number' => $number,
            'issue_date'     => $issueDate,
            'expiry_date'    => $expiryDate,
            'blood_group'    => $bloodGroup,
            'photo_url'      => $photoUrl,
            'details'        => $details ? json_encode($details) : null,
            'status'         => 'active',
            'generated_by'   => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function batchCreate(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");

        $this->view('dashboard.id-cards.batch', [
            'classes'  => $classes,
            'sections' => $sections,
        ]);
    }

    public function batchStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'class_id'    => 'required|numeric',
            'issue_date'  => 'required',
            'section_id'  => 'numeric',
            'expiry_date' => 'max:20',
        ]);

        $classId = (int) $data['class_id'];
        $sectionId = isset($data['section_id']) ? (int) $data['section_id'] : 0;
        $issueDate = $data['issue_date'];
        $expiryDate = $data['expiry_date'] ?? null;

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
            if ($this->storeOne((int) $student['id'], $issueDate, $expiryDate, null)) {
                $count++;
            }
        }

        Session::getInstance()->flash('success', "{$count} ID cards generated.");
        $this->redirect('/dashboard/id-cards');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }
        $this->view('dashboard.id-cards.show', ['card' => $card]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_number, u.name
             FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 500"
        );
        $this->view('dashboard.id-cards.edit', ['card' => $card, 'students' => $students]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }

        $data = $this->validate([
            'student_id'  => 'required|numeric',
            'issue_date'  => 'required',
            'expiry_date' => 'max:20',
            'blood_group' => 'max:10',
            'status'      => 'required',
        ]);

        $this->db->update('student_id_cards', [
            'student_id'   => (int) $data['student_id'],
            'issue_date'   => $data['issue_date'],
            'expiry_date'  => $data['expiry_date'] ?? null,
            'blood_group'  => $data['blood_group'] ?? null,
            'status'       => in_array($data['status'], ['active', 'expired', 'revoked'], true) ? $data['status'] : 'active',
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'ID card updated.');
        $this->redirect("/dashboard/id-cards/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->update('student_id_cards', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'ID card removed.');
        $this->redirect('/dashboard/id-cards');
    }

    public function print(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $preview = (bool) ($_GET['preview'] ?? false);
        $this->view('dashboard.id-cards.print', [
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
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/id-cards');
            return;
        }
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.id-cards.print', [
            'card'     => $card,
            'settings' => $settings,
            'preview'  => true,
        ]);
    }

    private function loadCard(int $id): ?array
    {
        $card = $this->db->fetch(
            "SELECT ic.*, u.name as student_name, s.admission_number, s.roll_number,
                    c.name as class_name, sec.name as section_name
             FROM student_id_cards ic
             LEFT JOIN students s ON ic.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE ic.id = ? AND ic.deleted_at IS NULL LIMIT 1",
            [$id]
        );
        if (!$card) {
            return null;
        }
        $card['details'] = isset($card['details']) && $card['details'] !== '' ? json_decode((string) $card['details'], true) : [];
        return $card;
    }

    // ----- Student ID cards (dashboard.student-id-cards.*) -----

    public function idCardIndex(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $where = 'ic.deleted_at IS NULL';
        $params = [];
        if ($search !== '') {
            $where .= " AND (ic.id_card_number LIKE ? OR EXISTS (SELECT 1 FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ic.student_id AND u.name LIKE ?))";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM student_id_cards ic WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT ic.* FROM student_id_cards ic WHERE {$where} ORDER BY ic.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.student-id-cards.index', [
            'idCards'  => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\StudentIdCard::class),
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
        ]);
    }

    public function idCardCreate(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.student-id-cards.create', [
            'students'     => $this->buildStudents(),
            'siteSettings' => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    public function idCardStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'  => 'required|numeric',
            'issue_date'  => 'required',
            'expiry_date' => 'max:20',
            'blood_group' => 'max:10',
            'photo_url'   => 'max:255',
            'details'     => 'array',
        ]);

        $this->storeOne(
            (int) $data['student_id'],
            $data['issue_date'],
            $data['expiry_date'] ?? null,
            $data['blood_group'] ?? null,
            $data['photo_url'] ?? null,
            $this->buildDetails()
        );

        Session::getInstance()->flash('success', 'ID card created.');
        $this->redirect('/dashboard/student-id-cards');
    }

    public function idCardBatchCreate(): void
    {
        Auth::requireAuth();
        $classes = new \App\Core\Support\Collection(\App\Models\SchoolClass::hydrate(
            $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC")
        ));
        $sections = new \App\Core\Support\Collection(\App\Models\Section::hydrate(
            $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC")
        ));

        $this->view('dashboard.student-id-cards.batch', [
            'classes'  => $classes,
            'sections' => $sections,
        ]);
    }

    public function idCardBatchStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'class_id'    => 'required|numeric',
            'issue_date'  => 'required',
            'section_id'  => 'numeric',
            'expiry_date' => 'max:20',
            'details'     => 'array',
        ]);

        $classId = (int) $data['class_id'];
        $sectionId = isset($data['section_id']) ? (int) $data['section_id'] : 0;
        $issueDate = $data['issue_date'];
        $expiryDate = $data['expiry_date'] ?? null;
        $details = $this->buildDetails();

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
            if ($this->storeOne((int) $student['id'], $issueDate, $expiryDate, null, null, $details)) {
                $count++;
            }
        }

        Session::getInstance()->flash('success', "{$count} ID cards generated.");
        $this->redirect('/dashboard/student-id-cards');
    }

    public function idCardShow(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadIdCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/student-id-cards');
            return;
        }
        $this->view('dashboard.student-id-cards.show', ['studentIdCard' => $card]);
    }

    public function idCardEdit(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadIdCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/student-id-cards');
            return;
        }
        $this->view('dashboard.student-id-cards.edit', [
            'studentIdCard' => $card,
            'students'      => $this->buildStudents(),
            'siteSettings'  => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    public function idCardUpdate(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadIdCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/student-id-cards');
            return;
        }

        $data = $this->validate([
            'student_id'  => 'required|numeric',
            'issue_date'  => 'required',
            'expiry_date' => 'max:20',
            'blood_group' => 'max:10',
            'photo_url'   => 'max:255',
            'status'      => 'required',
            'details'     => 'array',
        ]);

        $this->db->update('student_id_cards', [
            'student_id'  => (int) $data['student_id'],
            'issue_date'  => $data['issue_date'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'photo_url'   => $data['photo_url'] ?? null,
            'details'     => $this->detailsJson(),
            'status'      => in_array($data['status'], ['active', 'expired', 'revoked'], true) ? $data['status'] : 'active',
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'ID card updated.');
        $this->redirect("/dashboard/student-id-cards/{$id}");
    }

    public function idCardDestroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->update('student_id_cards', [
            'deleted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'ID card removed.');
        $this->redirect('/dashboard/student-id-cards');
    }

    public function idCardPrint(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadIdCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/student-id-cards');
            return;
        }
        $this->view('dashboard.student-id-cards.print', [
            'studentIdCard' => $card,
            'preview'       => (bool) ($_GET['preview'] ?? false),
            'siteSettings'  => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    public function idCardPreview(int $id): void
    {
        Auth::requireAuth();
        $card = $this->loadIdCard($id);
        if (!$card) {
            Session::getInstance()->flash('error', 'ID card not found.');
            $this->redirect('/dashboard/student-id-cards');
            return;
        }
        $this->view('dashboard.student-id-cards.print', [
            'studentIdCard' => $card,
            'preview'       => true,
            'siteSettings'  => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    private function buildStudents(int $limit = 500): \App\Core\Support\Collection
    {
        $rows = $this->db->fetchAll(
            "SELECT s.*, u.name AS user_name, u.email AS user_email
             FROM students s JOIN users u ON s.user_id = u.id
             ORDER BY s.id DESC LIMIT {$limit}"
        );
        $students = \App\Models\Student::hydrate($rows);
        foreach ($students as $student) {
            $student->setRelation('user', \App\Models\User::newFromRow([
                'id'    => $student->user_id,
                'name'  => $student->user_name,
                'email' => $student->user_email,
            ]));
        }
        return new \App\Core\Support\Collection($students);
    }

    private function buildDetails(): array
    {
        $details = $_POST['details'] ?? [];
        return [
            'header_text'  => isset($details['header_text']) && $details['header_text'] !== '' ? $details['header_text'] : null,
            'footer_text'  => isset($details['footer_text']) && $details['footer_text'] !== '' ? $details['footer_text'] : null,
            'show_logo'    => !isset($details['show_logo']) || (int) $details['show_logo'] === 1,
            'custom_notes' => isset($details['custom_notes']) && $details['custom_notes'] !== '' ? $details['custom_notes'] : null,
        ];
    }

    private function detailsJson(): ?string
    {
        $details = $this->buildDetails();
        return $details ? json_encode($details) : null;
    }

    private function loadIdCard(int $id): ?\App\Models\StudentIdCard
    {
        $row = $this->db->fetch(
            "SELECT ic.*, u.name AS user_name, u.email AS user_email, s.admission_number,
                    s.roll_number, c.name AS class_name, sec.name AS section_name
             FROM student_id_cards ic
             LEFT JOIN students s ON ic.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             LEFT JOIN sections sec ON s.section_id = sec.id
             WHERE ic.id = ? AND ic.deleted_at IS NULL LIMIT 1",
            [$id]
        );
        if (!$row) {
            return null;
        }
        $card = \App\Models\StudentIdCard::newFromRow($row);

        $student = \App\Models\Student::newFromRow([
            'id'               => $row['student_id'] ?? null,
            'admission_number' => $row['admission_number'] ?? null,
            'roll_number'      => $row['roll_number'] ?? null,
            'class_id'         => $row['class_id'] ?? null,
            'section_id'       => $row['section_id'] ?? null,
            'user_id'          => $row['user_id'] ?? null,
        ]);
        if (!empty($row['user_id'])) {
            $student->setRelation('user', \App\Models\User::newFromRow([
                'id'    => $row['user_id'],
                'name'  => $row['user_name'] ?? null,
                'email' => $row['user_email'] ?? null,
            ]));
        } else {
            $student->setRelation('user', null);
        }
        if (!empty($row['class_id'])) {
            $student->setRelation('class', \App\Models\SchoolClass::newFromRow([
                'id'   => $row['class_id'],
                'name' => $row['class_name'] ?? null,
            ]));
        } else {
            $student->setRelation('class', null);
        }
        if (!empty($row['section_id'])) {
            $student->setRelation('section', \App\Models\Section::newFromRow([
                'id'   => $row['section_id'],
                'name' => $row['section_name'] ?? null,
            ]));
        } else {
            $student->setRelation('section', null);
        }
        $card->setRelation('student', $student);

        return $card;
    }
}