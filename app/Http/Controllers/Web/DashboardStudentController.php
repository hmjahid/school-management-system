<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StudentController;
use App\Models\Batch;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardStudentController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Student::class);

        return view('dashboard.students.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'batches' => Batch::orderByDesc('id')->limit(50)->get(),
            'guardians' => Guardian::with('user')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(StudentController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.students')->with('status', __('Student created.'));
        }

        return $response;
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);
        $student->load(['user', 'class', 'section', 'batch', 'guardian.user']);

        return view('dashboard.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);
        $student->load(['user', 'class', 'section', 'batch', 'guardian']);

        return view('dashboard.students.edit', [
            'student' => $student,
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'batches' => Batch::orderByDesc('id')->limit(50)->get(),
            'guardians' => Guardian::with('user')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        $response = app(StudentController::class)->update($request, $student);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.students.show', $student)->with('status', __('Student updated.'));
        }

        return $response;
    }

    public function destroy(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        $response = app(StudentController::class)->destroy($student);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.students')->with('status', __('Student removed.'));
        }

        return $response;
    }
}
