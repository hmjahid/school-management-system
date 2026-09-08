<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Routine;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardRoutineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Routine::class);
        $query = Routine::with(['schoolClass', 'section', 'subject', 'teacher.user', 'batch']);
        $type = $request->input('type', Routine::TYPE_CLASS);
        if (! array_key_exists($type, Routine::getTypes())) {
            $type = Routine::TYPE_CLASS;
        }
        $query->where('type', $type);
        if ($classId = $request->integer('class_id')) {
            $query->where('school_class_id', $classId);
        }
        if ($sectionId = $request->integer('section_id')) {
            $query->where('section_id', $sectionId);
        }
        if ($day = $request->integer('day')) {
            $query->where('day_of_week', $day);
        }
        $routines = $query->orderBy('day_of_week')->orderBy('start_time')->paginate(20)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();

        return view('dashboard.routines.index', compact('routines', 'classes', 'sections', 'type'));
    }

    public function create(): View
    {
        $this->authorize('create', Routine::class);

        return view('dashboard.routines.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
            'batches' => Batch::orderBy('id')->limit(100)->get(),
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'days' => Routine::DAYS,
            'types' => Routine::getTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Routine::class);
        $validated = $request->validate([
            'type' => 'nullable|in:class,exam',
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'batch_id' => 'nullable|exists:batches,id',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required',
            'end_time' => 'required',
            'room_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        Routine::create($validated);

        return redirect()->route('dashboard.routines.index')->with('status', __('Routine entry created.'));
    }

    public function show(Routine $routine): View
    {
        $this->authorize('view', $routine);
        $routine->load(['schoolClass', 'section', 'subject', 'teacher.user', 'batch', 'academicSession']);

        return view('dashboard.routines.show', ['routine' => $routine]);
    }

    public function edit(Routine $routine): View
    {
        $this->authorize('update', $routine);

        return view('dashboard.routines.edit', [
            'routine' => $routine,
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
            'batches' => Batch::orderBy('id')->limit(100)->get(),
            'sessions' => AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get(),
            'days' => Routine::DAYS,
            'types' => Routine::getTypes(),
        ]);
    }

    public function update(Request $request, Routine $routine): RedirectResponse
    {
        $this->authorize('update', $routine);
        $validated = $request->validate([
            'type' => 'nullable|in:class,exam',
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'batch_id' => 'nullable|exists:batches,id',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required',
            'end_time' => 'required',
            'room_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $routine->update($validated);

        return redirect()->route('dashboard.routines.index')->with('status', __('Routine updated.'));
    }

    public function destroy(Routine $routine): RedirectResponse
    {
        $this->authorize('delete', $routine);
        $routine->delete();

        return redirect()->route('dashboard.routines.index')->with('status', __('Routine entry removed.'));
    }

    public function timetable(Request $request): View
    {
        $classId = $request->integer('class_id');
        $sectionId = $request->integer('section_id');

        $routines = Routine::with(['subject', 'teacher.user', 'schoolClass', 'section'])
            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->where('is_active', true)
            ->orderBy('day_of_week')->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $classes = SchoolClass::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();

        return view('site.routines', compact('routines', 'classes', 'sections', 'classId', 'sectionId'));
    }
}
