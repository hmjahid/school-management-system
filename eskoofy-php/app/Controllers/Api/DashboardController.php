<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        $stats = [
            'students'        => $db->count('students'),
            'active_students' => $db->count('students', "status = 'active'"),
            'teachers'        => $db->count('teachers'),
            'classes'         => $db->count('school_classes'),
            'exams'           => $db->count('exams'),
            'pending_admissions' => $db->count('admissions', "status = 'pending'"),
            'events'          => $db->count('events'),
            'notices'         => $db->count('notices', 'is_published = 1'),
        ];

        $this->success($stats, 'Dashboard stats retrieved');
    }
}