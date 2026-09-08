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
