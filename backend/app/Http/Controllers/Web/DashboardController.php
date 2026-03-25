<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
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
        ]);
    }

    /**
     * @return array{totalStudents: int, totalTeachers: int, totalParents: int, totalRevenue: float|int, attendanceRate: int}
     */
    protected function stats(): array
    {
        $defaults = [
            'totalStudents' => 0,
            'totalTeachers' => 0,
            'totalParents' => 0,
            'totalRevenue' => 0,
            'attendanceRate' => 0,
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
        } catch (\Throwable) {
            //
        }

        return $defaults;
    }
}
