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
use Illuminate\Support\Facades\DB;
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

    public function promoteForm(Request $request): View
    {
        $this->authorize('update', Student::class);

        $classes = SchoolClass::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();
        $batches = Batch::orderByDesc('id')->limit(50)->get();

        $students = collect();
        $fromClassId = $request->input('from_class_id');
        $fromSectionId = $request->input('from_section_id');

        if ($fromClassId) {
            $query = Student::query()
                ->where('class_id', $fromClassId)
                ->with(['user', 'class', 'section', 'batch']);

            if ($fromSectionId) {
                $query->where('section_id', $fromSectionId);
            }

            $students = $query->orderBy('roll_number')->get();
        }

        return view('dashboard.students.promote', compact(
            'classes',
            'sections',
            'batches',
            'students',
            'fromClassId',
            'fromSectionId'
        ));
    }

    public function promote(Request $request): RedirectResponse
    {
        $this->authorize('update', Student::class);

        $data = $request->validate([
            'from_class_id' => ['required', 'exists:school_classes,id'],
            'from_section_id' => ['nullable', 'exists:sections,id'],
            'to_class_id' => ['required', 'exists:school_classes,id'],
            'to_section_id' => ['nullable', 'exists:sections,id'],
            'to_batch_id' => ['required', 'exists:batches,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'keep_roll_number' => ['nullable', 'boolean'],
            'promote_all' => ['nullable', 'boolean'],
        ]);

        $query = Student::query()
            ->where('class_id', $data['from_class_id']);

        if (! empty($data['from_section_id'])) {
            $query->where('section_id', $data['from_section_id']);
        }

        if (empty($data['promote_all']) && ! empty($data['student_ids'])) {
            $query->whereIn('id', $data['student_ids']);
        } elseif (empty($data['promote_all'])) {
            return redirect()->route('dashboard.students.promote')
                ->with('status', __('No students selected for promotion.'));
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            return redirect()->route('dashboard.students.promote')
                ->with('status', __('No matching students found to promote.'));
        }

        $toClassId = $data['to_class_id'];
        $toSectionId = $data['to_section_id'] ?? null;
        $toBatchId = $data['to_batch_id'];
        $keepRoll = ! empty($data['keep_roll_number']);

        DB::transaction(function () use ($students, $toClassId, $toSectionId, $toBatchId, $keepRoll) {
            foreach ($students as $student) {
                $from = [
                    'class_id' => $student->class_id,
                    'section_id' => $student->section_id,
                    'batch_id' => $student->batch_id,
                ];

                $student->class_id = $toClassId;
                $student->section_id = $toSectionId;
                $student->batch_id = $toBatchId;

                if (! $keepRoll) {
                    $student->roll_number = null;
                }

                $student->save();

                activity()
                    ->performedOn($student)
                    ->withProperties([
                        'student_id' => $student->id,
                        'from' => $from,
                        'to' => [
                            'class_id' => $toClassId,
                            'section_id' => $toSectionId,
                            'batch_id' => $toBatchId,
                        ],
                    ])
                    ->log('student_promoted');
            }
        });

        return redirect()->route('dashboard.students.promote')
            ->with('status', __('Promoted :count student(s) to the next class.', ['count' => $students->count()]));
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
