<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentIdCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardStudentIdCardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StudentIdCard::class);
        $query = StudentIdCard::with(['student.user', 'generatedBy']);
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('id_card_number', 'like', "%{$search}%")
                    ->orWhereHas('student.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
        $idCards = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.student-id-cards.index', compact('idCards'));
    }

    public function create(): View
    {
        $this->authorize('create', StudentIdCard::class);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.student-id-cards.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StudentIdCard::class);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'blood_group' => 'nullable|string|max:10',
            'photo_url' => 'nullable|string|max:255',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);
        $student = Student::findOrFail($validated['student_id']);
        $validated['id_card_number'] = StudentIdCard::generateNumber($student);
        $validated['generated_by'] = $request->user()->id;
        $validated['details'] = $this->buildDetails($request);
        StudentIdCard::create($validated);

        return redirect()->route('dashboard.student-id-cards.index')->with('status', __('ID card created.'));
    }

    public function show(StudentIdCard $studentIdCard): View
    {
        $this->authorize('view', $studentIdCard);
        $studentIdCard->load(['student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.student-id-cards.show', compact('studentIdCard'));
    }

    public function edit(StudentIdCard $studentIdCard): View
    {
        $this->authorize('update', $studentIdCard);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.student-id-cards.edit', compact('studentIdCard', 'students'));
    }

    public function update(Request $request, StudentIdCard $studentIdCard): RedirectResponse
    {
        $this->authorize('update', $studentIdCard);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'blood_group' => 'nullable|string|max:10',
            'photo_url' => 'nullable|string|max:255',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
            'status' => 'required|string|in:active,expired,revoked',
        ]);
        $validated['details'] = $this->buildDetails($request);
        $studentIdCard->update($validated);

        return redirect()->route('dashboard.student-id-cards.index')->with('status', __('ID card updated.'));
    }

    public function destroy(StudentIdCard $studentIdCard): RedirectResponse
    {
        $this->authorize('delete', $studentIdCard);
        $studentIdCard->delete();

        return redirect()->route('dashboard.student-id-cards.index')->with('status', __('ID card removed.'));
    }

    public function print(StudentIdCard $studentIdCard, Request $request): View
    {
        $this->authorize('view', $studentIdCard);
        $studentIdCard->load(['student.user', 'student.class', 'student.section']);
        $preview = $request->boolean('preview');

        return view('dashboard.student-id-cards.print', compact('studentIdCard', 'preview'));
    }

    public function preview(StudentIdCard $studentIdCard): View
    {
        $this->authorize('view', $studentIdCard);
        $studentIdCard->load(['student.user', 'student.class', 'student.section']);

        return view('dashboard.student-id-cards.print', ['studentIdCard' => $studentIdCard, 'preview' => true]);
    }

    public function batchCreate(): View
    {
        $this->authorize('create', StudentIdCard::class);

        return view('dashboard.student-id-cards.batch', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function batchStore(Request $request): RedirectResponse
    {
        $this->authorize('create', StudentIdCard::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);

        $students = Student::query()
            ->where('class_id', $validated['class_id'])
            ->when($validated['section_id'] ?? null, fn ($q) => $q->where('section_id', $validated['section_id']))
            ->whereHas('user')
            ->get();

        $count = 0;
        DB::transaction(function () use ($students, $validated, $request, &$count) {
            foreach ($students as $student) {
                if (StudentIdCard::where('student_id', $student->id)->exists()) {
                    continue;
                }

                StudentIdCard::create([
                    'student_id' => $student->id,
                    'issue_date' => $validated['issue_date'],
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'id_card_number' => StudentIdCard::generateNumber($student),
                    'generated_by' => $request->user()->id,
                    'details' => $this->buildDetails($request),
                ]);
                $count++;
            }
        });

        return redirect()->route('dashboard.student-id-cards.index')
            ->with('status', __(':count ID cards generated.', ['count' => $count]));
    }

    protected function buildDetails(Request $request): array
    {
        return [
            'header_text' => $request->input('details.header_text', null),
            'footer_text' => $request->input('details.footer_text', null),
            'show_logo' => $request->boolean('details.show_logo', true),
            'custom_notes' => $request->input('details.custom_notes', null),
        ];
    }
}
