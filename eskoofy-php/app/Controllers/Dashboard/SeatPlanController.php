<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SeatPlanController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $where = '1=1';
        $params = [];
        if (isset($_GET['published']) && $_GET['published'] !== '') {
            $where .= ' AND is_published = ?';
            $params[] = (int) (bool) $_GET['published'];
        }

        $total = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM exams WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $examRows = $db->fetchAll(
            "SELECT * FROM exams WHERE {$where} ORDER BY start_date DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $exams = $this->paginateRows($examRows, $total, $perPage, $page, \App\Models\Exam::class);

        $this->view('dashboard.seat-plans.index', ['exams' => $exams]);
    }

    public function generate(int $examId): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $exam = $db->fetch("SELECT * FROM exams WHERE id = ?", [$examId]);
        if (!$exam) {
            Session::getInstance()->flash('error', 'Exam not found.');
            $this->redirect('/dashboard/seat-plans');
            return;
        }

        $perRoom = (int) ($_GET['per_room'] ?? 30);
        if ($perRoom < 1) {
            $perRoom = 30;
        }

        $where = 's.batch_id = ? AND s.status = ?';
        $params = [$exam['batch_id'] ?? 0, 'active'];
        if (!empty($exam['section_id'])) {
            $where .= ' AND s.section_id = ?';
            $params[] = $exam['section_id'];
        }

        $studentRows = $db->fetchAll(
            "SELECT s.id, s.user_id, s.admission_number, s.roll_number, s.first_name, s.last_name
             FROM students s
             WHERE {$where}
             ORDER BY s.roll_number ASC",
            $params
        );

        $rooms = [];
        $roomNumber = 1;
        foreach (array_chunk($studentRows, $perRoom) as $chunk) {
            $rooms['Room-' . $roomNumber] = new \App\Core\Support\Collection(\App\Models\Student::hydrate($chunk));
            $roomNumber++;
        }

        $date = !empty($exam['start_date']) ? date('d M Y', strtotime((string) $exam['start_date'])) : 'N/A';

        $this->view('dashboard.seat-plans.show', [
            'exam'     => \App\Models\Exam::newFromRow($exam),
            'rooms'    => $rooms,
            'perRoom'  => $perRoom,
            'settings' => \App\Models\WebsiteSetting::getSettings(),
            'date'     => $date,
            'preview'  => (bool) ($_GET['view'] ?? false),
        ]);
    }
}
