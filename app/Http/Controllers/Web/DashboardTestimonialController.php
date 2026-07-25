<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardTestimonialController extends Controller
{
    public function index(): View
    {
        $rows = Testimonial::with('student')->orderByDesc('id')->paginate(20);

        return view('dashboard.testimonials.index', compact('rows'));
    }

    public function create(): View
    {
        return view('dashboard.testimonials.create', [
            'testimonial' => new Testimonial(['is_visible' => true, 'rating' => 5]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTestimonial($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('testimonials', 'public');
        }

        $data['is_visible'] = $request->boolean('is_visible');

        Testimonial::create($data);

        return redirect()->route('dashboard.testimonials.index')->with('status', __('Testimonial created.'));
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('dashboard.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $this->validateTestimonial($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('testimonials', 'public');
        }

        $data['is_visible'] = $request->boolean('is_visible');

        $testimonial->fill($data)->save();

        return back()->with('status', __('Testimonial updated.'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('dashboard.testimonials.index')->with('status', __('Testimonial deleted.'));
    }

    public function toggleVisibility(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['is_visible' => !$testimonial->is_visible]);

        return back()->with('status', __('Visibility toggled.'));
    }

    protected function validateTestimonial(Request $request): array
    {
        return $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_designation' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);
    }
}
