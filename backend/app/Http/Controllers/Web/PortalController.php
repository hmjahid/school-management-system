<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'teacher', 'accountant', 'staff', 'librarian'])) {
            return redirect()->route('dashboard');
        }

        $student = null;
        $children = collect();
        $assignments = collect();
        $recentAttendance = collect();
        $examResults = collect();
        $feePayments = collect();
        $announcements = collect();

        if ($user->hasRole('student')) {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->with(['class', 'section', 'batch'])
                ->first();

            if ($student) {
                if ($student->batch_id) {
                    $assignments = Assignment::query()
                        ->where('batch_id', $student->batch_id)
                        ->orderByDesc('due_date')
                        ->limit(15)
                        ->get();
                }

                $recentAttendance = Attendance::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('date')
                    ->limit(14)
                    ->get();

                $examResults = ExamResult::query()
                    ->where('student_id', $student->id)
                    ->where('is_published', true)
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get();

                $feePayments = FeePayment::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('payment_date')
                    ->limit(15)
                    ->get();
            }
        }

        if ($user->hasRole('parent')) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            if ($guardian) {
                $children = $guardian->students()
                    ->with(['class', 'section', 'batch'])
                    ->get();

                $ids = $children->pluck('id');
                if ($ids->isNotEmpty()) {
                    $recentAttendance = Attendance::query()
                        ->whereIn('student_id', $ids)
                        ->orderByDesc('date')
                        ->limit(20)
                        ->get();

                    $examResults = ExamResult::query()
                        ->whereIn('student_id', $ids)
                        ->where('is_published', true)
                        ->orderByDesc('id')
                        ->limit(15)
                        ->get();

                    $feePayments = FeePayment::query()
                        ->whereIn('student_id', $ids)
                        ->orderByDesc('payment_date')
                        ->limit(20)
                        ->get();
                }
            }
        }

        $audience = $user->hasRole('parent') ? 'parent' : 'student';
        $announcements = Announcement::query()
            ->published()
            ->active()
            ->whereIn('audience', ['all', $audience])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('site.portal', compact(
            'user',
            'student',
            'children',
            'assignments',
            'recentAttendance',
            'examResults',
            'feePayments',
            'announcements'
        ));
    }
}
