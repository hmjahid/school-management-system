<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardAttendanceController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        return view('dashboard.attendance.create', [
            'students' => Student::with(['user', 'class'])->orderByDesc('id')->limit(400)->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
            'batches' => Batch::orderByDesc('id')->limit(80)->get(),
            'sections' => Section::orderBy('name')->limit(200)->get(),
            'subjects' => Subject::orderBy('name')->limit(200)->get(),
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'statuses' => Attendance::getStatuses(),
            'types' => Attendance::getTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(AttendanceController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.attendance')->with('status', __('Attendance recorded.'));
        }
        if ($response instanceof JsonResponse && $response->getStatusCode() === 422) {
            $msg = DashboardWebHelper::jsonErrorMessage($response) ?? __('Could not save attendance.');

            return back()->withInput()->withErrors(['attendance' => $msg]);
        }

        return $response;
    }

    public function bulk(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        $session = AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->first();
        $batchId = $request->integer('batch_id') ?: Batch::orderBy('name')->value('id');
        $sectionId = $request->integer('section_id') ?: null;
        $date = $request->filled('date') ? Carbon::parse($request->string('date')->toString()) : now();

        $students = collect();
        if ($batchId) {
            $query = Student::with('class')->where('batch_id', $batchId);
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }
            $students = $query->orderBy('roll_no')->limit(120)->get();
        }

        $existing = Attendance::where('date', $date->toDateString())
            ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->get()
            ->keyBy('student_id');

        return view('dashboard.attendance.bulk', [
            'students' => $students,
            'batches' => Batch::orderByDesc('id')->limit(80)->get(),
            'sections' => Section::orderBy('name')->limit(200)->get(),
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'statuses' => Attendance::getStatuses(),
            'date' => $date,
            'batchId' => $batchId,
            'sectionId' => $sectionId,
            'existing' => $existing,
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Attendance::getTypes()))],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'string', 'in:'.implode(',', array_keys(Attendance::getStatuses()))],
            'remarks' => ['nullable', 'array'],
            'remarks.*' => ['nullable', 'string', 'max:500'],
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $type = $data['type'] ?? Attendance::TYPE_DAILY;
        $count = 0;

        foreach ($data['status'] as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'student_id' => (int) $studentId,
                    'date' => $date,
                    'type' => $type,
                ],
                [
                    'status' => $status,
                    'batch_id' => $data['batch_id'] ?? null,
                    'section_id' => $data['section_id'] ?? null,
                    'remarks' => $data['remarks'][$studentId] ?? null,
                    'recorded_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ],
            );
            $count++;
        }

        return redirect()
            ->route('dashboard.attendance.bulk', [
                'date' => $date,
                'batch_id' => $data['batch_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
            ])
            ->with('status', __('Saved :count attendance records.', ['count' => $count]));
    }
}
