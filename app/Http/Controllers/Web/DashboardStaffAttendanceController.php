<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardStaffAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('manage_teacher_attendance'), 403);

        $date = $request->filled('date') ? Carbon::parse($request->string('date')->toString()) : now();

        $teachers = Teacher::with('user')->orderBy('id')->get();
        $existing = StaffAttendance::whereDate('date', $date->toDateString())
            ->get()
            ->keyBy('teacher_id');

        return view('dashboard.staff-attendance.index', compact('teachers', 'existing', 'date'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_teacher_attendance'), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'string', 'in:'.implode(',', array_keys(StaffAttendance::STATUSES))],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:500'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();

        foreach ($data['status'] as $teacherId => $status) {
            $existing = StaffAttendance::where('teacher_id', (int) $teacherId)
                ->whereDate('date', $date)
                ->first();
            if ($existing) {
                $existing->update([
                    'status' => $status,
                    'note' => $data['note'][$teacherId] ?? null,
                    'recorded_by' => $request->user()->id,
                ]);
            } else {
                StaffAttendance::create([
                    'teacher_id' => (int) $teacherId,
                    'date' => $date,
                    'status' => $status,
                    'note' => $data['note'][$teacherId] ?? null,
                    'recorded_by' => $request->user()->id,
                ]);
            }
        }

        return back()->with('status', __('Staff attendance saved.'));
    }

    public function report(Request $request): View
    {
        abort_unless($request->user()?->can('view_attendance_reports'), 403);

        $month = $request->filled('month') ? Carbon::parse($request->string('month')->toString().'-01') : now()->startOfMonth();
        $period = CarbonPeriod::create($month->copy()->startOfMonth(), $month->copy()->endOfMonth());

        $teachers = Teacher::with('user')->orderBy('id')->get();
        $records = StaffAttendance::whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()
            ->groupBy('teacher_id');

        return view('dashboard.staff-attendance.report', compact('teachers', 'records', 'month', 'period'));
    }
}
