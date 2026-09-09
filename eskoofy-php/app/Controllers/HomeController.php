<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        $settings = $db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $news = $db->fetchAll(
            "SELECT * FROM news WHERE is_event = 0 AND status = 'published' ORDER BY published_at DESC LIMIT 5"
        );

        $events = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date >= CURRENT_DATE ORDER BY start_date ASC LIMIT 5"
        );

        $notices = $db->fetchAll(
            "SELECT * FROM notices ORDER BY pinned DESC, id DESC LIMIT 5"
        );

        $teachers = $db->fetchAll(
            "SELECT t.*, u.name, u.photo FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.status = 'active' ORDER BY t.id DESC LIMIT 8"
        );

        $testimonials = $db->fetchAll(
            "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY id DESC LIMIT 10"
        );

        $committeeMembers = $db->fetchAll(
            "SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 20"
        );

        $studentCount = $db->count('students');
        $teacherCount = $db->count('teachers');
        $classCount = $db->count('school_classes');

        $years = null;
        if ($settings && !empty($settings['established_year'])) {
            $years = (int) date('Y') - (int) $settings['established_year'];
        }

        $stats = [
            'total_students' => $studentCount,
            'total_teachers' => $teacherCount,
            'total_classes'  => $classCount,
            'years'          => $years,
        ];

        $this->view('site.home', [
            'settings'         => $settings,
            'news'             => $news,
            'latestNews'       => $news,
            'events'           => $events,
            'upcomingEvents'   => $events,
            'notices'          => $notices,
            'recentNotices'    => $notices,
            'teachers'         => $teachers,
            'testimonials'     => $testimonials,
            'committeeMembers' => $committeeMembers,
            'stats'            => $stats,
        ]);
    }
}
