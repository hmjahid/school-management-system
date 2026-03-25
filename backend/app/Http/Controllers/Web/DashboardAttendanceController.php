<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AttendanceController;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
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
}
