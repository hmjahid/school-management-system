<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardCertificateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);
        $query = Certificate::with(['student.user', 'generatedBy']);
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhereHas('student.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('certificate_type', $type);
        }
        $certificates = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.certificates.index', compact('certificates'));
    }

    public function create(): View
    {
        $this->authorize('create', Certificate::class);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.certificates.create', [
            'students' => $students,
            'types' => Certificate::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'certificate_type' => 'required|string|in:'.implode(',', Certificate::TYPES),
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,issued',
            'body' => 'nullable|string',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);
        $validated['certificate_number'] = Certificate::generateNumber();
        $validated['generated_by'] = $request->user()->id;
        $validated['created_by'] = $request->user()->id;
        $student = Student::with('user')->find($validated['student_id']);
        $validated['name'] = __(':type certificate for :student', [
            'type' => __(ucfirst($validated['certificate_type'])),
            'student' => $student?->user?->name ?? __('Student'),
        ]);
        if (! isset($validated['template']) || is_null($validated['template'])) {
            $validated['template'] = [];
        }
        if (! empty($validated['body'])) {
            $validated['body'] = [$validated['body']];
        }
        $validated['details'] = $this->buildDetails($request);
        Certificate::create($validated);

        return redirect()->route('dashboard.certificates.index')->with('status', __('Certificate created.'));
    }

    public function show(Certificate $certificate): View
    {
        $this->authorize('view', $certificate);
        $certificate->load(['student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.certificates.show', compact('certificate'));
    }

    public function edit(Certificate $certificate): View
    {
        $this->authorize('update', $certificate);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.certificates.edit', [
            'certificate' => $certificate,
            'students' => $students,
            'types' => Certificate::TYPES,
        ]);
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $this->authorize('update', $certificate);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'certificate_type' => 'required|string|in:'.implode(',', Certificate::TYPES),
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,issued,revoked',
            'body' => 'nullable|string',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
        ]);
        if (! empty($validated['body'])) {
            $validated['body'] = [$validated['body']];
        }
        $validated['details'] = $this->buildDetails($request);
        $certificate->update($validated);

        return redirect()->route('dashboard.certificates.index')->with('status', __('Certificate updated.'));
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $this->authorize('delete', $certificate);
        $certificate->delete();

        return redirect()->route('dashboard.certificates.index')->with('status', __('Certificate removed.'));
    }

    public function print(Certificate $certificate): View
    {
        $this->authorize('view', $certificate);
        $certificate->load(['student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.certificates.print', compact('certificate'));
    }

    protected function buildDetails(Request $request): array
    {
        $existing = [];

        return [
            'header_text' => $request->input('details.header_text', $existing['header_text'] ?? null),
            'footer_text' => $request->input('details.footer_text', $existing['footer_text'] ?? null),
            'show_logo' => $request->boolean('details.show_logo', true),
            'custom_notes' => $request->input('details.custom_notes', $existing['custom_notes'] ?? null),
        ];
    }
}
