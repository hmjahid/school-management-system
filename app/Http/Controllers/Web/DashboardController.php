<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(\App\Services\DashboardService $service): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(['admin', 'teacher', 'accountant', 'staff', 'librarian'])) {
            return redirect()->route('portal');
        }

        $user->load(['roles', 'permissions']);

        return view('dashboard.index', [
            'user' => $user,
            'roleNames' => $user->getRoleNames()->implode(', ') ?: __('No role assigned'),
            'stats' => $this->stats(),
            'attendanceStats' => $service->attendanceStats(),
        ]);
    }

    /**
     * @return array{totalStudents: int, totalTeachers: int, totalParents: int, totalRevenue: float|int, attendanceRate: int, pendingAdmissions: int, pendingDues: int}
     */
    protected function stats(): array
    {
        $defaults = [
            'totalStudents' => 0,
            'totalTeachers' => 0,
            'totalParents' => 0,
            'totalRevenue' => 0,
            'attendanceRate' => 0,
            'pendingAdmissions' => 0,
            'pendingDues' => 0,
        ];

        try {
            if (Schema::hasTable('students')) {
                $defaults['totalStudents'] = Student::count();
            }
            if (Schema::hasTable('users')) {
                $defaults['totalTeachers'] = User::role('teacher')->count();
                $defaults['totalParents'] = User::role('parent')->count();
            }
            if (Schema::hasTable('payments')) {
                $defaults['totalRevenue'] = (float) Payment::query()
                    ->where('payment_status', Payment::STATUS_COMPLETED)
                    ->sum('paid_amount');
            }
            if (Schema::hasTable('attendances')) {
                $from = now()->subDays(7)->startOfDay();
                $total = Attendance::query()->where('date', '>=', $from)->count();
                $present = Attendance::query()
                    ->where('date', '>=', $from)
                    ->whereIn('status', [
                        Attendance::STATUS_PRESENT,
                        Attendance::STATUS_LATE,
                        Attendance::STATUS_HALF_DAY,
                    ])
                    ->count();
                $defaults['attendanceRate'] = $total > 0 ? (int) round(100 * $present / $total) : 0;
            }
            if (Schema::hasTable('admissions')) {
                $defaults['pendingAdmissions'] = Admission::query()
                    ->where('status', Admission::STATUS_SUBMITTED)
                    ->count();
            }
            if (Schema::hasTable('fee_payments')) {
                $defaults['pendingDues'] = FeePayment::query()
                    ->whereIn('status', [FeePayment::STATUS_PENDING, FeePayment::STATUS_PARTIAL])
                    ->sum('balance');
            }
        } catch (\Throwable) {
            //
        }

        return $defaults;
    }
}
