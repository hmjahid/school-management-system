<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\ExamResult;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\Routine;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        $upcomingEvents = collect();
        $routine = collect();
        $teachers = collect();
        $attendanceCalendar = collect();
        $duesTimeline = collect();

        if (Schema::hasTable('events')) {
            $upcomingEvents = Event::query()
                ->where('start_date', '>=', now()->startOfDay())
                ->orderBy('start_date')
                ->limit(10)
                ->get();
        }

        if ($user->hasRole('student')) {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->with(['class', 'section', 'batch'])
                ->first();

            if ($student) {
                $this->loadStudentData($student, $assignments, $recentAttendance, $examResults, $feePayments, $routine, $teachers);
                $attendanceCalendar = $this->attendanceCalendar([$student->id]);
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

                    $attendanceCalendar = $this->attendanceCalendar($ids->all());

                    $classIds = $children->pluck('class_id')->filter()->unique();
                    $teachers = $this->teachersForClasses($classIds);
                }
            }
        }

        $duesTimeline = $feePayments->sortByDesc(fn ($fp) => $fp->payment_date ?? $fp->created_at)->values();

        $audience = $user->hasRole('parent') ? 'parent' : 'student';
        $announcements = Announcement::query()
            ->published()
            ->active()
            ->where(function ($q) use ($audience) {
                $q->whereJsonContains('audience', 'all')
                    ->orWhereJsonContains('audience', $audience);
            })
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
            'announcements',
            'upcomingEvents',
            'routine',
            'teachers',
            'attendanceCalendar',
            'duesTimeline'
        ));
    }

    public function messageTeacher(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => 'required|integer|exists:teachers,id',
            'subject' => 'required|string|max:120',
            'body' => 'required|string|max:2000',
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);

        if (Schema::hasTable('messages')) {
            Message::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $teacher->user_id,
                'subject' => $data['subject'],
                'body' => $data['body'],
            ]);
        }

        return redirect()->route('portal')->with('status', site_ui('portal.message_sent'));
    }

    private function loadStudentData(
        Student $student,
        mixed &$assignments,
        mixed &$recentAttendance,
        mixed &$examResults,
        mixed &$feePayments,
        mixed &$routine,
        mixed &$teachers
    ): void {
        if ($student->batch_id && Schema::hasTable('assignments')) {
            $assignments = Assignment::query()
                ->where('batch_id', $student->batch_id)
                ->orderByDesc('due_date')
                ->limit(15)
                ->get();
        }

        if (Schema::hasTable('attendances')) {
            $recentAttendance = Attendance::query()
                ->where('student_id', $student->id)
                ->orderByDesc('date')
                ->limit(14)
                ->get();
        }

        if (Schema::hasTable('exam_results')) {
            $examResults = ExamResult::query()
                ->where('student_id', $student->id)
                ->where('is_published', true)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        if (Schema::hasTable('fee_payments')) {
            $feePayments = FeePayment::query()
                ->where('student_id', $student->id)
                ->orderByDesc('payment_date')
                ->limit(15)
                ->get();
        }

        if (Schema::hasTable('routines')) {
            $routine = Routine::query()
                ->where('school_class_id', $student->class_id)
                ->where('is_active', true)
                ->with(['subject', 'teacher.user'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week');
        }

        if ($student->class_id && Schema::hasTable('class_teacher') && Schema::hasTable('teachers')) {
            $teachers = $this->teachersForClasses(collect([$student->class_id]));
        }
    }

    /**
     * @param  array<int, int>|\Illuminate\Support\Collection  $studentIds
     */
    private function attendanceCalendar(array $studentIds): mixed
    {
        if (empty($studentIds) || ! Schema::hasTable('attendances')) {
            return collect();
        }

        return Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->where('date', '>=', now()->subDays(30)->startOfDay())
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn ($a) => $a->date?->format('Y-m-d') ?? 'unknown');
    }

    private function teachersForClasses(\Illuminate\Support\Collection $classIds): mixed
    {
        if ($classIds->isEmpty() || ! Schema::hasTable('class_teacher')) {
            return collect();
        }

        $teacherIds = \Illuminate\Support\Facades\DB::table('class_teacher')
            ->whereIn('class_id', $classIds)
            ->pluck('teacher_id')
            ->unique()
            ->values();

        if ($teacherIds->isEmpty()) {
            return collect();
        }

        return Teacher::query()
            ->whereIn('id', $teacherIds)
            ->with('user')
            ->get();
    }
}
