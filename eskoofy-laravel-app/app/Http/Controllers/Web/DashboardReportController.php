<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardReportController extends Controller
{
    public function index(): View
    {
        return view('dashboard.reports.index');
    }

    public function fees(Request $request): View
    {
        $from = $this->parseDate($request->input('from'), now()->subMonths(6));
        $to = $this->parseDate($request->input('to'), now());

        $byMonth = collect();
        $byStatus = collect();
        $byMethod = collect();
        $total = 0.0;
        $count = 0;

        if (Schema::hasTable('payments')) {
            $base = Payment::query()
                ->whereBetween('payment_date', [$from->startOfDay(), $to->endOfDay()]);

            $byMonth = (clone $base)
                ->selectRaw("strftime('%Y-%m', payment_date) as bucket, SUM(paid_amount) as total, COUNT(*) as count")
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            $byStatus = (clone $base)
                ->selectRaw('payment_status, SUM(paid_amount) as total, COUNT(*) as count')
                ->groupBy('payment_status')
                ->get();

            $byMethod = (clone $base)
                ->selectRaw('payment_method, SUM(paid_amount) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->get();

            $total = (float) (clone $base)->sum('paid_amount');
            $count = (clone $base)->count();
        }

        return view('dashboard.reports.fees', [
            'from' => $from,
            'to' => $to,
            'byMonth' => $byMonth,
            'byStatus' => $byStatus,
            'byMethod' => $byMethod,
            'total' => $total,
            'count' => $count,
        ]);
    }

    public function attendance(Request $request): View
    {
        $from = $this->parseDate($request->input('from'), now()->subDays(30));
        $to = $this->parseDate($request->input('to'), now());

        $byClass = collect();
        $byDate = collect();
        $total = 0;
        $present = 0;

        if (Schema::hasTable('attendances')) {
            $base = Attendance::query()->whereBetween('date', [$from->startOfDay(), $to->endOfDay()]);

            $byClass = (clone $base)
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->join('school_classes', 'students.class_id', '=', 'school_classes.id')
                ->selectRaw('school_classes.name as class_name,
                    SUM(CASE WHEN attendances.status IN (\'present\',\'late\',\'half_day\') THEN 1 ELSE 0 END) as present_count,
                    COUNT(*) as total_count')
                ->groupBy('school_classes.name')
                ->orderBy('school_classes.name')
                ->get();

            $byDate = (clone $base)
                ->selectRaw("date(attendances.date) as day,
                    SUM(CASE WHEN attendances.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count,
                    COUNT(*) as total_count")
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $total = (clone $base)->count();
            $present = (clone $base)
                ->whereIn('status', ['present', 'late', 'half_day'])
                ->count();
        }

        $rate = $total > 0 ? round(100 * $present / $total, 1) : 0;

        return view('dashboard.reports.attendance', [
            'from' => $from,
            'to' => $to,
            'byClass' => $byClass,
            'byDate' => $byDate,
            'total' => $total,
            'present' => $present,
            'rate' => $rate,
        ]);
    }

    public function students(Request $request): View
    {
        $byClass = collect();
        $byStatus = collect();
        $byGender = collect();
        $total = 0;

        if (Schema::hasTable('students')) {
            $byClass = Student::query()
                ->leftJoin('school_classes', 'students.class_id', '=', 'school_classes.id')
                ->selectRaw('school_classes.name as class_name, COUNT(*) as total')
                ->groupBy('school_classes.name')
                ->orderBy('school_classes.name')
                ->get();

            $byStatus = Student::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get();

            $byGender = Student::query()
                ->selectRaw('gender, COUNT(*) as total')
                ->whereNotNull('gender')
                ->groupBy('gender')
                ->get();

            $total = Student::count();
        }

        return view('dashboard.reports.students', [
            'byClass' => $byClass,
            'byStatus' => $byStatus,
            'byGender' => $byGender,
            'total' => $total,
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['fees', 'attendance', 'students'], true), 404);
        $filename = "report-{$type}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');

            if ($type === 'fees') {
                fputcsv($out, ['month', 'total', 'count']);
                Payment::query()
                    ->selectRaw("strftime('%Y-%m', payment_date) as bucket, SUM(paid_amount) as total, COUNT(*) as count")
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->get()
                    ->each(fn ($r) => fputcsv($out, [$r->bucket, $r->total, $r->count]));
            } elseif ($type === 'attendance') {
                fputcsv($out, ['date', 'class', 'present', 'total']);
                Attendance::query()
                    ->join('students', 'attendances.student_id', '=', 'students.id')
                    ->join('school_classes', 'students.class_id', '=', 'school_classes.id')
                    ->selectRaw("date(attendances.date) as day,
                        school_classes.name as class_name,
                        SUM(CASE WHEN attendances.status IN ('present','late','half_day') THEN 1 ELSE 0 END) as present_count,
                        COUNT(*) as total_count")
                    ->groupBy('day', 'class_name')
                    ->orderBy('day')
                    ->get()
                    ->each(fn ($r) => fputcsv($out, [$r->day, $r->class_name, $r->present_count, $r->total_count]));
            } else {
                fputcsv($out, ['class', 'count']);
                Student::query()
                    ->leftJoin('school_classes', 'students.class_id', '=', 'school_classes.id')
                    ->selectRaw('school_classes.name, COUNT(*) as total')
                    ->groupBy('school_classes.name')
                    ->get()
                    ->each(fn ($r) => fputcsv($out, [$r->name, $r->total]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function analytics(Request $request): View
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i))->all();
        $monthLabels = collect($months)->map(fn ($m) => $m->format('M Y'))->all();

        $studentGrowth = array_fill(0, 12, 0);
        $revenue = array_fill(0, 12, 0.0);
        $expenses = array_fill(0, 12, 0.0);
        $feeTarget = 0.0;
        $feeCollected = 0.0;
        $attendanceByClass = collect();
        $teacherWorkload = collect();

        try {
            if (Schema::hasTable('students')) {
                Student::query()
                    ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', created_at) as bucket, COUNT(*) as total")
                    ->groupBy('bucket')
                    ->get()
                    ->each(function ($row) use (&$studentGrowth, $months) {
                        foreach ($months as $idx => $m) {
                            if ($m->format('Y-m') === $row->bucket) {
                                $studentGrowth[$idx] = (int) $row->total;
                            }
                        }
                    });
            }

            if (Schema::hasTable('payments')) {
                Payment::query()
                    ->where('payment_status', Payment::STATUS_COMPLETED)
                    ->where('payment_date', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', payment_date) as bucket, SUM(paid_amount) as total")
                    ->groupBy('bucket')
                    ->get()
                    ->each(function ($row) use (&$revenue, $months) {
                        foreach ($months as $idx => $m) {
                            if ($m->format('Y-m') === $row->bucket) {
                                $revenue[$idx] = (float) $row->total;
                            }
                        }
                    });

                $feeCollected = (float) Payment::query()
                    ->where('payment_status', Payment::STATUS_COMPLETED)
                    ->where('payment_date', '>=', now()->startOfMonth())
                    ->sum('paid_amount');
            }

            if (Schema::hasTable('expenses')) {
                Expense::query()
                    ->where('date', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("strftime('%Y-%m', date) as bucket, SUM(amount) as total")
                    ->groupBy('bucket')
                    ->get()
                    ->each(function ($row) use (&$expenses, $months) {
                        foreach ($months as $idx => $m) {
                            if ($m->format('Y-m') === $row->bucket) {
                                $expenses[$idx] = (float) $row->total;
                            }
                        }
                    });
            }

            if (Schema::hasTable('fees') && Schema::hasTable('students')) {
                $totalStudents = Student::query()->where('status', 'active')->count();
                $monthlyFees = \App\Models\Fee::query()
                    ->where('status', 'active')
                    ->whereIn('frequency', ['monthly', 'recurring'])
                    ->sum('amount');
                $feeTarget = (float) $monthlyFees * max(1, $totalStudents);
            }

            if (Schema::hasTable('attendances')) {
                $from = now()->subDays(30);
                $attendanceByClass = Attendance::query()
                    ->where('date', '>=', $from->startOfDay())
                    ->join('students', 'attendances.student_id', '=', 'students.id')
                    ->join('school_classes', 'students.class_id', '=', 'school_classes.id')
                    ->selectRaw('school_classes.name as class_name,
                        ROUND(100.0 * SUM(CASE WHEN attendances.status IN (\'present\',\'late\',\'half_day\') THEN 1 ELSE 0 END) / COUNT(*), 1) as rate')
                    ->groupBy('school_classes.name')
                    ->orderBy('school_classes.name')
                    ->get();
            }

            if (Schema::hasTable('class_teacher') && Schema::hasTable('teachers') && Schema::hasTable('users')) {
                $teacherWorkload = \App\Models\Teacher::query()
                    ->join('users', 'teachers.user_id', '=', 'users.id')
                    ->selectRaw('users.name as teacher_name, COUNT(DISTINCT class_teacher.class_id) as classes_count')
                    ->join('class_teacher', 'teachers.id', '=', 'class_teacher.teacher_id')
                    ->groupBy('users.name')
                    ->orderByDesc('classes_count')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable) {
            //
        }

        return view('dashboard.reports.analytics', [
            'months' => $monthLabels,
            'studentGrowth' => $studentGrowth,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'feeTarget' => $feeTarget,
            'feeCollected' => $feeCollected,
            'feeTargetPercent' => $feeTarget > 0 ? round(100 * $feeCollected / $feeTarget, 1) : 0,
            'attendanceByClass' => $attendanceByClass,
            'teacherWorkload' => $teacherWorkload,
        ]);
    }

    protected function parseDate(mixed $raw, Carbon $fallback): Carbon
    {
        if (! $raw) {
            return $fallback;
        }
        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
