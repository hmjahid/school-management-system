<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Assignment::class);
        $query = Assignment::with(['subject', 'batch', 'createdBy']);
        if ($search = $request->string('search')->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($batchId = $request->integer('batch_id')) {
            $query->where('batch_id', $batchId);
        }
        $assignments = $query->latest()->paginate(15)->withQueryString();
        $batches = Batch::orderBy('id')->limit(100)->get();

        return view('dashboard.assignments.index', compact('assignments', 'batches'));
    }

    public function create(): View
    {
        $this->authorize('create', Assignment::class);

        return view('dashboard.assignments.create', [
            'batches' => Batch::orderBy('id')->limit(100)->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Assignment::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'batch_id' => 'required|exists:batches,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'due_date' => 'required|date',
            'total_marks' => 'nullable|integer|min:0',
            'allow_guardian_notes' => 'nullable|boolean',
            'file' => 'nullable|file|max:10240',
        ]);
        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('assignments', 'public');
        }
        $validated['created_by'] = $request->user()->id;
        $validated['allow_guardian_notes'] = $request->boolean('allow_guardian_notes');
        Assignment::create($validated);

        return redirect()->route('dashboard.assignments.index')->with('status', __('Assignment created.'));
    }

    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        $assignment->load(['subject', 'batch', 'class', 'section', 'createdBy', 'submissions.student.user']);

        return view('dashboard.assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorize('update', $assignment);

        return view('dashboard.assignments.edit', [
            'assignment' => $assignment,
            'batches' => Batch::orderBy('id')->limit(100)->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'batch_id' => 'required|exists:batches,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'due_date' => 'required|date',
            'total_marks' => 'nullable|integer|min:0',
            'allow_guardian_notes' => 'nullable|boolean',
            'file' => 'nullable|file|max:10240',
        ]);
        if ($request->hasFile('file')) {
            if ($assignment->file_path) {
                Storage::disk('public')->delete($assignment->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('assignments', 'public');
        }
        $validated['allow_guardian_notes'] = $request->boolean('allow_guardian_notes');
        $assignment->update($validated);

        return redirect()->route('dashboard.assignments.index')->with('status', __('Assignment updated.'));
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);
        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }
        $assignment->delete();

        return redirect()->route('dashboard.assignments.index')->with('status', __('Assignment removed.'));
    }

    public function submissions(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);
        $assignment->load(['submissions.student.user', 'submissions.guardian.user', 'subject', 'batch', 'class', 'section']);

        return view('dashboard.assignments.submissions', compact('assignment'));
    }

    public function grade(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        $this->authorize('update', $submission->assignment);
        $validated = $request->validate([
            'marks' => 'required|integer|min:0|max:'.$submission->assignment->total_marks,
            'feedback' => 'nullable|string|max:1000',
        ]);
        $validated['graded_by'] = $request->user()->id;
        $validated['graded_at'] = now();
        $validated['status'] = AssignmentSubmission::STATUS_GRADED;
        $submission->update($validated);

        return back()->with('status', __('Submission graded.'));
    }
}
