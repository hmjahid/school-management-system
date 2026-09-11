<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Schema;
use App\Core\Support\Collection;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $user = Auth::user();
        $stats = $this->stats();
        $attendanceStats = $this->attendanceStats();
        $revenueExpense = $this->revenueExpenseTrend();
        $workbench = null;

        $setupItems = [
            ['key' => 'school_settings', 'label' => __('Dashboard Settings'), 'done' => Schema::hasTable('website_settings') && (bool) Schema::hasTable('website_settings')],
            ['key' => 'students', 'label' => __('Add Students'), 'done' => $stats['totalStudents'] > 0],
            ['key' => 'teachers', 'label' => __('Add Teachers'), 'done' => $stats['totalTeachers'] > 0],
            ['key' => 'classes', 'label' => __('Create Classes'), 'done' => ($stats['totalClasses'] ?? 0) > 0],
        ];
        $doneCount = count(array_filter($setupItems, fn ($i) => $i['done']));
        $setupPercent = (int) round(100 * $doneCount / max(1, count($setupItems)));
        $setupComplete = $doneCount === count($setupItems);

        $this->view('dashboard.index', [
            'user'          => $user,
            'roleNames'     => $user ? ($user->attributes['role'] ?? '') : '',
            'stats'         => $stats,
            'attendanceStats' => $attendanceStats,
            'revenueExpense' => $revenueExpense,
            'setupItems'    => $setupItems,
            'setupPercent'  => $setupPercent,
            'setupComplete' => $setupComplete,
            'workbench'     => $workbench,
        ]);
    }

    protected function stats(): array
    {
        $defaults = [
            'totalStudents'       => 0,
            'totalTeachers'       => 0,
            'totalClasses'        => 0,
            'totalParents'        => 0,
            'pendingAdmissions'   => 0,
            'attendanceRate'      => 0,
            'totalRevenue'        => 0,
            'pendingDues'         => 0,
            'totalExpenses'       => 0,
        ];
        try {
            if (Schema::hasTable('students')) {
                $defaults['totalStudents'] = (int) \App\Models\Student::query()->count();
            }
            if (Schema::hasTable('teachers')) {
                $defaults['totalTeachers'] = (int) \App\Models\Teacher::query()->count();
            }
            if (Schema::hasTable('school_classes')) {
                $defaults['totalClasses'] = (int) \App\Models\SchoolClass::query()->count();
            }
            if (Schema::hasTable('guardians')) {
                $defaults['totalParents'] = (int) \App\Models\Guardian::query()->count();
            }
            if (Schema::hasTable('admissions')) {
                $defaults['pendingAdmissions'] = (int) \App\Models\Admission::query()->where('status', 'submitted')->count();
            }
            if (Schema::hasTable('payments')) {
                $defaults['totalRevenue'] = (float) (\App\Models\Payment::query()
                    ->where('payment_status', 'completed')
                    ->sum('paid_amount'));
            }
            if (Schema::hasTable('fee_payments')) {
                $defaults['pendingDues'] = (float) \App\Models\FeePayment::query()->whereRaw("status IN ('pending', 'partial')")->sum('balance');
            }
            if (Schema::hasTable('expenses')) {
                $defaults['totalExpenses'] = (float) \App\Models\Expense::query()->sum('amount');
            }
        } catch (\Throwable) {
            //
        }
        return $defaults;
    }

    protected function attendanceStats(): array
    {
        $defaults = [
            'today'       => 0,
            'present_today' => 0,
            'absent_today'  => 0,
            'late_today'    => 0,
            'leave_today'   => 0,
            'today_rate'    => 0,
            'trend'         => [],
        ];
        try {
            if (Schema::hasTable('attendances')) {
                $today = date('Y-m-d');
                $defaults['today'] = (int) \App\Models\Attendance::query()->whereDate('date', $today)->count();
                $defaults['present_today'] = (int) \App\Models\Attendance::query()->whereDate('date', $today)->whereIn('status', ['present', 'late', 'half_day'])->count();
                $defaults['absent_today'] = (int) \App\Models\Attendance::query()->whereDate('date', $today)->where('status', 'absent')->count();
                $defaults['late_today'] = (int) \App\Models\Attendance::query()->whereDate('date', $today)->where('status', 'late')->count();
                $defaults['today_rate'] = $defaults['today'] > 0 ? (int) round(100 * $defaults['present_today'] / $defaults['today']) : 0;
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $day = date('Y-m-d', strtotime("-{$i} days"));
                    $total = (int) \App\Models\Attendance::query()->whereDate('date', $day)->count();
                    $present = (int) \App\Models\Attendance::query()->whereDate('date', $day)->whereIn('status', ['present', 'late', 'half_day'])->count();
                    $trend[] = ['date' => $day, 'rate' => $total > 0 ? (int) round(100 * $present / $total) : 0];
                }
                $defaults['trend'] = $trend;
            }
        } catch (\Throwable) {
            //
        }
        return $defaults;
    }

    protected function revenueExpenseTrend(): array
    {
        $months = [];
        $revenue = [];
        $expenses = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = date('M', strtotime("-{$i} months"));
            $revenue[] = 0;
            $expenses[] = 0;
        }
        return [
            'months'   => $months,
            'revenue'  => $revenue,
            'expenses' => $expenses,
        ];
    }
}