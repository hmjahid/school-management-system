<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class ResultsController extends Controller
{
    public function lookup(): void
    {
        $db = Database::getInstance();
        $admissionNumber = $_GET['admission_number'] ?? '';
        $examId = (int) ($_GET['exam_id'] ?? 0);

        if ($admissionNumber === '') {
            $this->error('admission_number is required', 422);
        }

        $student = $db->fetch(
            "SELECT s.id, s.admission_number, u.name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.admission_number = ? LIMIT 1",
            [$admissionNumber]
        );

        if (!$student) {
            $this->error('Student not found', 404);
        }

        $where = 'er.student_id = ?';
        $params = [$student['id']];
        if ($examId > 0) {
            $where .= ' AND er.exam_id = ?';
            $params[] = $examId;
        }

        $results = $db->fetchAll(
            "SELECT er.obtained_marks, er.total_marks, er.passing_marks, er.grade, er.remarks,
                    e.name as exam_name, e.exam_type, e.exam_date, sub.name as subject_name
             FROM exam_results er
             LEFT JOIN exams e ON er.exam_id = e.id
             LEFT JOIN subjects sub ON er.subject_id = sub.id
             WHERE {$where}
             ORDER BY e.exam_date DESC, sub.name ASC",
            $params
        );

        $this->success([
            'student' => $student,
            'results' => $results,
            'count'   => count($results),
        ], 'Results retrieved successfully');
    }
}