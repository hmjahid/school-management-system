<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdmitCard;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardAdmitCardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdmitCard::class);
        $query = AdmitCard::with(['exam', 'student.user', 'generatedBy']);
        if ($examId = $request->integer('exam_id')) {
            $query->where('exam_id', $examId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->whereHas('student.user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhere('admit_card_number', 'like', "%{$search}%");
        }
        $admitCards = $query->latest()->paginate(15)->withQueryString();
        $exams = Exam::orderByDesc('id')->limit(50)->get();

        return view('dashboard.admit-cards.index', compact('admitCards', 'exams'));
    }

    public function create(): View
    {
        $this->authorize('create', AdmitCard::class);
        $exams = Exam::orderByDesc('id')->limit(50)->get();
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.admit-cards.create', compact('exams', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AdmitCard::class);
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);
        $exam = Exam::findOrFail($validated['exam_id']);
        $student = Student::findOrFail($validated['student_id']);
        $validated['admit_card_number'] = AdmitCard::generateNumber($exam, $student);
        $validated['generated_by'] = $request->user()->id;
        $validated['details'] = $this->buildDetails($request);
        AdmitCard::create($validated);

        return redirect()->route('dashboard.admit-cards.index')->with('status', __('Admit card generated.'));
    }

    public function show(AdmitCard $admitCard): View
    {
        $this->authorize('view', $admitCard);
        $admitCard->load(['exam', 'student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.admit-cards.show', compact('admitCard'));
    }

    public function edit(AdmitCard $admitCard): View
    {
        $this->authorize('update', $admitCard);
        $admitCard->load(['exam', 'student.user']);
        $exams = Exam::orderByDesc('id')->limit(50)->get();
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.admit-cards.edit', compact('admitCard', 'exams', 'students'));
    }

    public function update(Request $request, AdmitCard $admitCard): RedirectResponse
    {
        $this->authorize('update', $admitCard);
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'status' => 'required|string|in:issued,revoked',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);
        $exam = Exam::findOrFail($validated['exam_id']);
        $student = Student::findOrFail($validated['student_id']);
        $validated['admit_card_number'] = AdmitCard::generateNumber($exam, $student);
        $validated['details'] = $this->buildDetails($request);
        $admitCard->update($validated);

        return redirect()->route('dashboard.admit-cards.index')->with('status', __('Admit card updated.'));
    }

    public function destroy(AdmitCard $admitCard): RedirectResponse
    {
        $this->authorize('delete', $admitCard);
        $admitCard->delete();

        return redirect()->route('dashboard.admit-cards.index')->with('status', __('Admit card removed.'));
    }

    public function print(AdmitCard $admitCard, Request $request): View
    {
        $this->authorize('view', $admitCard);
        $admitCard->load(['exam', 'student.user', 'student.class', 'student.section']);
        $preview = $request->boolean('preview');

        return view('dashboard.admit-cards.print', compact('admitCard', 'preview'));
    }

    public function preview(AdmitCard $admitCard): View
    {
        $this->authorize('view', $admitCard);
        $admitCard->load(['exam', 'student.user', 'student.class', 'student.section']);

        return view('dashboard.admit-cards.print', ['admitCard' => $admitCard, 'preview' => true]);
    }

    public function batchCreate(): View
    {
        $this->authorize('create', AdmitCard::class);

        return view('dashboard.admit-cards.batch', [
            'exams' => Exam::orderByDesc('id')->limit(100)->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function batchStore(Request $request): RedirectResponse
    {
        $this->authorize('create', AdmitCard::class);

        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'issue_date' => 'required|date',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        $students = Student::query()
            ->where('class_id', $validated['class_id'])
            ->when($validated['section_id'] ?? null, fn ($q) => $q->where('section_id', $validated['section_id']))
            ->whereHas('user')
            ->get();

        $count = 0;
        DB::transaction(function () use ($students, $exam, $validated, $request, &$count) {
            foreach ($students as $student) {
                if (AdmitCard::where('exam_id', $exam->id)->where('student_id', $student->id)->exists()) {
                    continue;
                }

                AdmitCard::create([
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'issue_date' => $validated['issue_date'],
                    'admit_card_number' => AdmitCard::generateNumber($exam, $student),
                    'generated_by' => $request->user()->id,
                    'details' => $this->buildDetails($request),
                ]);
                $count++;
            }
        });

        return redirect()->route('dashboard.admit-cards.index')
            ->with('status', __(':count admit cards generated.', ['count' => $count]));
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
