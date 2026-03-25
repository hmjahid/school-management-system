<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TeacherController;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardTeacherController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Teacher::class);

        return view('dashboard.teachers.create', [
            'subjects' => Subject::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(TeacherController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.teachers')->with('status', __('Teacher created.'));
        }

        return $response;
    }

    public function show(Teacher $teacher): View
    {
        $this->authorize('view', $teacher);
        $teacher->load(['user', 'subjects', 'classes']);

        return view('dashboard.teachers.show', compact('teacher'));
    }

    public function edit(Teacher $teacher): View
    {
        $this->authorize('update', $teacher);
        $teacher->load(['user', 'subjects', 'classes']);

        return view('dashboard.teachers.edit', [
            'teacher' => $teacher,
            'subjects' => Subject::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse|JsonResponse
    {
        $response = app(TeacherController::class)->update($request, $teacher);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.teachers.show', $teacher)->with('status', __('Teacher updated.'));
        }

        return $response;
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse|JsonResponse
    {
        $response = app(TeacherController::class)->destroy($teacher);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.teachers')->with('status', __('Teacher removed.'));
        }

        return $response;
    }
}
