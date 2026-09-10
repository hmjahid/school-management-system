<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class SearchController extends Controller
{
    public function search(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $q = trim((string) ($_GET['q'] ?? ''));

        $results = [
            'students' => [],
            'teachers' => [],
            'classes'  => [],
            'exams'    => [],
            'fees'     => [],
        ];

        if ($q !== '') {
            $like = "%{$q}%";
            $results['students'] = $db->fetchAll(
                "SELECT s.id, s.admission_number, u.name, c.name as class_name
                 FROM students s LEFT JOIN users u ON s.user_id = u.id
                 LEFT JOIN school_classes c ON s.class_id = c.id
                 WHERE u.name LIKE ? OR s.admission_number LIKE ? LIMIT 20",
                [$like, $like]
            );
            $results['teachers'] = $db->fetchAll(
                "SELECT t.id, t.employee_id, u.name, u.email
                 FROM teachers t LEFT JOIN users u ON t.user_id = u.id
                 WHERE u.name LIKE ? OR t.employee_id LIKE ? LIMIT 20",
                [$like, $like]
            );
            $results['classes'] = $db->fetchAll(
                "SELECT id, name, code FROM school_classes WHERE name LIKE ? OR code LIKE ? LIMIT 20",
                [$like, $like]
            );
            $results['exams'] = $db->fetchAll(
                "SELECT id, name, start_date FROM exams WHERE name LIKE ? LIMIT 20",
                [$like]
            );
            $results['fees'] = $db->fetchAll(
                "SELECT id, name, amount FROM fees WHERE name LIKE ? LIMIT 20",
                [$like]
            );
        }

        $this->view('dashboard.search.index', [
            'q'       => $q,
            'results' => $results,
        ]);
    }
}
