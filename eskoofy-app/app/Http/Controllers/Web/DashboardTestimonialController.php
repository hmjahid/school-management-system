<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardTestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Testimonial::class);
        $query = Testimonial::with(['student.user', 'generatedBy']);
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('testimonial_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('student.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('testimonial_type', $type);
        }
        $testimonials = $query->latest()->paginate(15)->withQueryString();

        return view('dashboard.testimonials.index', compact('testimonials'));
    }

    public function create(): View
    {
        $this->authorize('create', Testimonial::class);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.testimonials.create', [
            'testimonial' => new Testimonial(['status' => 'draft', 'is_visible' => true]),
            'students' => $students,
            'types' => Testimonial::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Testimonial::class);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'testimonial_type' => 'required|string|in:'.implode(',', Testimonial::TYPES),
            'name' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,issued',
            'body' => 'nullable|string',
            'content' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'author_name' => 'nullable|string|max:255',
            'author_designation' => 'nullable|string|max:255',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
            'is_visible' => 'nullable|boolean',
        ]);

        $validated['testimonial_number'] = Testimonial::generateNumber();
        $validated['generated_by'] = $request->user()->id;

        if (! empty($validated['body'])) {
            $validated['body'] = [$validated['body']];
        }

        $validated['details'] = $this->buildDetails($request);
        $validated['is_visible'] = $request->boolean('is_visible');

        Testimonial::create($validated);

        return redirect()->route('dashboard.testimonials.index')->with('status', __('Testimonial created.'));
    }

    public function show(Testimonial $testimonial): View
    {
        $this->authorize('view', $testimonial);
        $testimonial->load(['student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.testimonials.show', compact('testimonial'));
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);
        $students = Student::with('user')->whereHas('user')->orderBy('id')->limit(500)->get();

        return view('dashboard.testimonials.edit', [
            'testimonial' => $testimonial,
            'students' => $students,
            'types' => Testimonial::TYPES,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'testimonial_type' => 'required|string|in:'.implode(',', Testimonial::TYPES),
            'name' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'status' => 'required|string|in:draft,issued,revoked',
            'body' => 'nullable|string',
            'content' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'author_name' => 'nullable|string|max:255',
            'author_designation' => 'nullable|string|max:255',
            'details' => 'nullable|array',
            'details.header_text' => 'nullable|string|max:255',
            'details.footer_text' => 'nullable|string|max:255',
            'details.show_logo' => 'nullable|boolean',
            'details.custom_notes' => 'nullable|string|max:1000',
            'is_visible' => 'nullable|boolean',
        ]);

        if (! empty($validated['body'])) {
            $validated['body'] = [$validated['body']];
        }

        $validated['details'] = $this->buildDetails($request);
        $validated['is_visible'] = $request->boolean('is_visible');

        $testimonial->update($validated);

        return redirect()->route('dashboard.testimonials.index')->with('status', __('Testimonial updated.'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);
        $testimonial->delete();

        return redirect()->route('dashboard.testimonials.index')->with('status', __('Testimonial deleted.'));
    }

    public function print(Testimonial $testimonial): View
    {
        $this->authorize('view', $testimonial);
        $testimonial->load(['student.user', 'student.class', 'student.section', 'generatedBy']);

        return view('dashboard.testimonials.print', compact('testimonial'));
    }

    protected function buildDetails(Request $request): array
    {
        return [
            'header_text' => $request->input('details.header_text'),
            'footer_text' => $request->input('details.footer_text'),
            'show_logo' => $request->boolean('details.show_logo', true),
            'custom_notes' => $request->input('details.custom_notes'),
        ];
    }
}
