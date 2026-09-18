<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardCareerController extends Controller
{
    public function postings(): View
    {
        $jobs = Career::withCount('applications')->latest()->get();

        return view('dashboard.careers.index', compact('jobs'));
    }

    public function create(): View
    {
        return view('dashboard.careers.form', ['job' => new Career]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCareer($request);
        Career::create($data);

        return redirect()->route('dashboard.careers.postings')->with('status', __('Career created.'));
    }

    public function edit(Career $career): View
    {
        return view('dashboard.careers.form', compact('career'));
    }

    public function update(Request $request, Career $career): RedirectResponse
    {
        $career->update($this->validateCareer($request));

        return redirect()->route('dashboard.careers.postings')->with('status', __('Career updated.'));
    }

    public function destroyCareer(Career $career): RedirectResponse
    {
        $career->applications()->delete();
        $career->delete();

        return redirect()->route('dashboard.careers.postings')->with('status', __('Career deleted.'));
    }

    private function validateCareer(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'type' => ['required', 'in:full-time,part-time,contract,internship'],
            'location' => ['required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['required', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }
    public function index(Request $request): View
    {
        $query = JobApplication::with('career')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('career_id')) {
            $query->where('career_id', $request->career_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(20);
        $careers = Career::orderBy('title')->get(['id', 'title']);

        return view('dashboard.careers.applications', compact('applications', 'careers'));
    }

    public function show(JobApplication $application): View
    {
        $application->load('career');

        return view('dashboard.careers.show', compact('application'));
    }

    public function updateStatus(Request $request, JobApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,shortlisted,rejected,hired',
        ]);

        $application->update(['status' => $validated['status']]);

        return back()->with('status', __('Application status updated.'));
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $application->delete();

        return back()->with('status', __('Application deleted.'));
    }
}
