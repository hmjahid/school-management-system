<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardLeaveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = LeaveRequest::query()->with(['teacher.user', 'type', 'approver']);

        // Non-admin users see only their own requests
        if (!$user->hasAnyRole(['admin', 'staff'])) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                abort(403);
            }
            $query->where('teacher_id', $teacher->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $rows = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('dashboard.leaves.index', compact('rows'));
    }

    public function create(Request $request): View
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        if (!$teacher && !$request->user()->hasAnyRole(['admin', 'staff'])) {
            abort(403);
        }

        return view('dashboard.leaves.create', [
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
            'types' => LeaveType::where('is_active', true)->orderBy('name_en')->get(),
            'teacher' => $teacher,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        // Non-admins can only create leave for themselves
        if (!$request->user()->hasAnyRole(['admin', 'staff'])) {
            $teacher = Teacher::where('user_id', $request->user()->id)->first();
            if (!$teacher || (int) $data['teacher_id'] !== $teacher->id) {
                abort(403);
            }
        }

        LeaveRequest::create($data + ['status' => LeaveRequest::STATUS_PENDING]);

        return redirect()->route('dashboard.leaves.index')->with('status', __('Leave request submitted.'));
    }

    public function show(Request $request, LeaveRequest $leave): View
    {
        $leave->load(['teacher.user', 'type', 'approver']);
        return view('dashboard.leaves.show', compact('leave'));
    }

    public function approve(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($request->user()->can('manage_teacher_attendance'), 403);

        $data = $request->validate([
            'approver_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave->approve($request->user()->id, $data['approver_note'] ?? null);

        return back()->with('status', __('Leave approved.'));
    }

    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($request->user()->can('manage_teacher_attendance'), 403);

        $data = $request->validate([
            'approver_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave->reject($request->user()->id, $data['approver_note'] ?? null);

        return back()->with('status', __('Leave rejected.'));
    }

    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($leave->teacher?->user_id === $request->user()->id || $request->user()->hasAnyRole(['admin', 'staff']), 403);

        $leave->cancel();

        return back()->with('status', __('Leave cancelled.'));
    }
}