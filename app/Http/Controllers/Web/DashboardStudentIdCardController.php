<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StudentIdCard;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                  ->orWhereHas('student.user', fn($u) => $u->where('name', 'like', "%{$search}%"));
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
        ]);
        $student = Student::findOrFail($validated['student_id']);
        $validated['id_card_number'] = StudentIdCard::generateNumber($student);
        $validated['generated_by'] = $request->user()->id;
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
            'status' => 'required|string|in:active,expired,revoked',
        ]);
        $studentIdCard->update($validated);
        return redirect()->route('dashboard.student-id-cards.index')->with('status', __('ID card updated.'));
    }

    public function destroy(StudentIdCard $studentIdCard): RedirectResponse
    {
        $this->authorize('delete', $studentIdCard);
        $studentIdCard->delete();
        return redirect()->route('dashboard.student-id-cards.index')->with('status', __('ID card removed.'));
    }

    public function print(StudentIdCard $studentIdCard): View
    {
        $this->authorize('view', $studentIdCard);
        $studentIdCard->load(['student.user', 'student.class', 'student.section']);
        return view('dashboard.student-id-cards.print', compact('studentIdCard'));
    }
}