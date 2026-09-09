<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $user = Auth::user();

        $totalStudents = $db->count('students');
        $totalTeachers = $db->count('teachers');
        $totalClasses = $db->count('school_classes');
        $totalSections = $db->count('sections');

        $totalRevenue = (float) ($db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM payments WHERE payment_status = 'completed'"
        )['total'] ?? 0);

        $totalExpenses = (float) ($db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses"
        )['total'] ?? 0);

        $pendingAdmissions = $db->count('admissions', "status = 'submitted'");

        $pendingDues = (float) ($db->fetch(
            "SELECT COALESCE(SUM(balance), 0) as total FROM fee_payments WHERE status IN ('pending', 'partial')"
        )['total'] ?? 0);

        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
        $totalAttendance = (int) ($db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date >= ?", [$sevenDaysAgo]
        )['cnt'] ?? 0);
        $presentAttendance = (int) ($db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date >= ? AND status IN ('present', 'late', 'half_day')",
            [$sevenDaysAgo]
        )['cnt'] ?? 0);
        $attendanceRate = $totalAttendance > 0 ? (int) round(100 * $presentAttendance / $totalAttendance) : 0;

        $recentStudents = $db->fetchAll(
            "SELECT s.*, u.name, c.name as class_name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN school_classes c ON s.class_id = c.id
             ORDER BY s.id DESC LIMIT 10"
        );

        $recentAdmissions = $db->fetchAll(
            "SELECT * FROM admissions ORDER BY submitted_at DESC LIMIT 5"
        );

        $recentPayments = $db->fetchAll(
            "SELECT p.*, u.name as creator_name
             FROM payments p
             LEFT JOIN users u ON p.created_by = u.id
             ORDER BY p.id DESC LIMIT 10"
        );

        $upcomingEvents = $db->fetchAll(
            "SELECT * FROM events WHERE status = 'published' AND start_date >= CURRENT_DATE ORDER BY start_date ASC LIMIT 5"
        );

        $recentActivity = $db->fetchAll(
            "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10"
        );

        $todayPresent = (int) ($db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date = CURRENT_DATE AND status IN ('present', 'late', 'half_day')"
        )['cnt'] ?? 0);
        $todayTotal = (int) ($db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date = CURRENT_DATE"
        )['cnt'] ?? 0);

        $stats = [
            'total_students'        => $totalStudents,
            'total_teachers'        => $totalTeachers,
            'total_classes'         => $totalClasses,
            'total_sections'        => $totalSections,
            'students_growth'       => 0,
            'teachers_growth'       => 0,
            'fees_collected'        => $totalRevenue,
            'fees_pending'          => $pendingDues,
            'fees_growth'           => 0,
            'fees_collection_rate'  => $totalRevenue > 0 ? min(100, (int) round(100 * $totalRevenue / max(1, ($totalRevenue + $pendingDues)))) : 0,
            'total_fees'            => $totalRevenue + $pendingDues,
            'today_attendance_rate' => $attendanceRate,
            'present_today'         => $todayPresent,
            'absent_today'          => max(0, $todayTotal - $todayPresent),
            'late_today'            => 0,
        ];

        $this->view('dashboard.index', [
            'user'              => $user,
            'stats'             => $stats,
            'totalStudents'     => $totalStudents,
            'totalTeachers'     => $totalTeachers,
            'totalClasses'      => $totalClasses,
            'totalSections'     => $totalSections,
            'totalRevenue'      => $totalRevenue,
            'totalExpenses'     => $totalExpenses,
            'pendingAdmissions' => $pendingAdmissions,
            'pendingDues'       => $pendingDues,
            'attendanceRate'    => $attendanceRate,
            'todayPresent'      => $todayPresent,
            'todayTotal'        => $todayTotal,
            'recentStudents'    => $recentStudents,
            'recentAdmissions'  => $recentAdmissions,
            'recentPayments'    => $recentPayments,
            'recentActivity'    => $recentActivity,
            'upcomingEvents'    => $upcomingEvents,
        ]);
    }
}
