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

        $todayPresent = $db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date = CURDATE() AND status IN ('present', 'late', 'half_day')"
        )['cnt'] ?? 0;
        $todayTotal = $db->fetch(
            "SELECT COUNT(*) as cnt FROM attendances WHERE date = CURDATE()"
        )['cnt'] ?? 0;

        $this->view('dashboard.index', [
            'user'              => $user,
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
        ]);
    }
}
