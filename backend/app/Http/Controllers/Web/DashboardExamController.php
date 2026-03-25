<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExamController;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardExamController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Exam::class);

        return view('dashboard.exams.create', [
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'batches' => Batch::orderByDesc('id')->limit(100)->get(),
            'sections' => Section::orderBy('name')->limit(200)->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
            'examTypes' => Exam::getTypes(),
            'examStatuses' => Exam::getStatuses(),
            'gradingTypes' => Exam::getGradingTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(ExamController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.exams')->with('status', __('Exam created.'));
        }

        return $response;
    }
}
