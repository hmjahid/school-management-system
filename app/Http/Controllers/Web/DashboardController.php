<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(\App\Services\DashboardService $service, \App\Services\SetupChecklistService $setupChecklist): View|RedirectResponse
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
            'revenueExpense' => $this->revenueExpenseTrend(),
            'setupItems' => $setupChecklist->items(),
            'setupPercent' => $setupChecklist->completionPercent(),
            'setupComplete' => $setupChecklist->isComplete(),
            'workbench' => $this->workbench($user),
        ]);
    }

    /**
     * Role-focused quick stats & shortcuts (5.3) for non-admin views.
     */
    protected function workbench(\App\Models\User $user): ?array
    {
        $role = $user->roles->first()?->name;

        return match ($role) {
            'teacher' => $this->teacherWorkbench($user),
            'accountant' => $this->accountantWorkbench(),
            'librarian' => $this->librarianWorkbench(),
            default => null,
        };
    }

    private function teacherWorkbench(\App\Models\User $user): array
    {
        $defaults = [
            'classes' => 0,
            'unreadMessages' => 0,
            'upcomingExams' => 0,
        ];

        try {
            if (Schema::hasTable('teachers') && Schema::hasTable('class_teacher')) {
                $defaults['classes'] = \App\Models\Teacher::where('user_id', $user->id)
                    ->withCount('classes')
                    ->value('classes_count') ?? 0;
            }
            if (Schema::hasTable('messages')) {
                $defaults['unreadMessages'] = \App\Models\Message::where('receiver_id', $user->id)->unread()->count();
            }
            if (Schema::hasTable('exams')) {
                $defaults['upcomingExams'] = \App\Models\Exam::query()
                    ->where('exam_date', '>=', now()->startOfDay())
                    ->where('is_published', true)
                    ->count();
            }
        } catch (\Throwable) {
            //
        }

        return [
            'title' => __('My teaching'),
            'items' => [
                ['label' => __('My classes'), 'value' => $defaults['classes'], 'url' => route('dashboard.teachers')],
                ['label' => __('Unread messages'), 'value' => $defaults['unreadMessages'], 'url' => route('dashboard.communications')],
                ['label' => __('Upcoming exams'), 'value' => $defaults['upcomingExams'], 'url' => route('dashboard.exams')],
            ],
        ];
    }

    private function accountantWorkbench(): array
    {
        $defaults = [
            'pendingApprovals' => 0,
            'monthRevenue' => 0,
            'pendingDues' => 0,
        ];

        try {
            if (Schema::hasTable('fee_payments')) {
                $defaults['pendingApprovals'] = FeePayment::query()->where('status', FeePayment::STATUS_PENDING)->count();
                $defaults['pendingDues'] = (float) FeePayment::query()
                    ->whereIn('status', [FeePayment::STATUS_PENDING, FeePayment::STATUS_PARTIAL])
                    ->sum('balance');
            }
            if (Schema::hasTable('payments')) {
                $defaults['monthRevenue'] = (float) Payment::query()
                    ->where('payment_status', Payment::STATUS_COMPLETED)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('paid_amount');
            }
        } catch (\Throwable) {
            //
        }

        return [
            'title' => __('Finance overview'),
            'items' => [
                ['label' => __('Pending approvals'), 'value' => $defaults['pendingApprovals'], 'url' => route('dashboard.fee-payments.index')],
                ['label' => __('Collected this month'), 'value' => number_format($defaults['monthRevenue'], 2), 'url' => route('dashboard.fee-payments.index')],
                ['label' => __('Outstanding dues'), 'value' => number_format($defaults['pendingDues'], 2), 'url' => route('dashboard.fees')],
            ],
        ];
    }

    private function librarianWorkbench(): array
    {
        $defaults = [
            'issuedBooks' => 0,
            'books' => 0,
        ];

        try {
            if (Schema::hasTable('book_issues')) {
                $defaults['issuedBooks'] = \App\Models\BookIssue::query()
                    ->where('status', '!=', \App\Models\BookIssue::STATUS_RETURNED)
                    ->count();
            }
            if (Schema::hasTable('books')) {
                $defaults['books'] = \App\Models\Book::query()->count();
            }
        } catch (\Throwable) {
            //
        }

        return [
            'title' => __('Library overview'),
            'items' => [
                ['label' => __('Books in catalogue'), 'value' => $defaults['books'], 'url' => route('dashboard.library.books.index')],
                ['label' => __('Checked out'), 'value' => $defaults['issuedBooks'], 'url' => route('dashboard.library.issues.index')],
            ],
        ];
    }

    /**
     * @return array{months: array<int, string>, revenue: array<int, float>, expenses: array<int, float>}
     */
    protected function revenueExpenseTrend(): array
    {
        $months = collect(range(11, 0))
            ->map(fn ($i) => now()->subMonths($i)->format('M'))
            ->all();

        $revenue = array_fill(0, 12, 0.0);
        $expenses = array_fill(0, 12, 0.0);

        try {
            if (Schema::hasTable('payments')) {
                Payment::query()
                    ->where('payment_status', Payment::STATUS_COMPLETED)
                    ->where('payment_date', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', payment_date) as bucket, SUM(paid_amount) as total")
                    ->groupBy('bucket')
                    ->get()
                    ->each(function ($row) use (&$revenue, $months) {
                        $idx = array_search(now()->parse($row->bucket.'-01')->format('M'), $months);
                        if ($idx !== false) {
                            $revenue[$idx] = (float) $row->total;
                        }
                    });
            }

            if (Schema::hasTable('expenses')) {
                Expense::query()
                    ->where('date', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', date) as bucket, SUM(amount) as total")
                    ->groupBy('bucket')
                    ->get()
                    ->each(function ($row) use (&$expenses, $months) {
                        $idx = array_search(now()->parse($row->bucket.'-01')->format('M'), $months);
                        if ($idx !== false) {
                            $expenses[$idx] = (float) $row->total;
                        }
                    });
            }
        } catch (\Throwable) {
            //
        }

        return [
            'months' => $months,
            'revenue' => $revenue,
            'expenses' => $expenses,
        ];
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
