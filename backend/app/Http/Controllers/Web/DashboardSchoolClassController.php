<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SchoolClassController;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardSchoolClassController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', SchoolClass::class);

        return view('dashboard.classes.create', [
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(SchoolClassController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.classes')->with('status', __('Class created.'));
        }

        return $response;
    }

    public function show(SchoolClass $class): View
    {
        $this->authorize('view', $class);
        $class->load(['classTeacher.user', 'academicSession', 'sections']);

        return view('dashboard.classes.show', ['schoolClass' => $class]);
    }

    public function edit(SchoolClass $class): View
    {
        $this->authorize('update', $class);
        $class->load(['classTeacher', 'academicSession']);

        return view('dashboard.classes.edit', [
            'schoolClass' => $class,
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse|JsonResponse
    {
        $response = app(SchoolClassController::class)->update($request, $class);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.classes.show', $class)->with('status', __('Class updated.'));
        }

        return $response;
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse|JsonResponse
    {
        $response = app(SchoolClassController::class)->destroy($class);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.classes')->with('status', __('Class removed.'));
        }
        if ($response instanceof JsonResponse && $response->getStatusCode() === 422) {
            $msg = DashboardWebHelper::jsonErrorMessage($response) ?? __('Cannot remove this class.');

            return back()->withErrors(['delete' => $msg]);
        }

        return $response;
    }
}
