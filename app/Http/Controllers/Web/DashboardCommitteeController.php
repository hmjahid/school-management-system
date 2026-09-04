<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardCommitteeController extends Controller
{
    public function index(Request $request): View
    {
        $query = CommitteeMember::query();
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }
        $members = $query->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        return view('dashboard.committee.index', compact('members'));
    }

    public function create(): View
    {
        return view('dashboard.committee.create', [
            'member' => new CommitteeMember(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'designation' => 'required|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string|max:1000',
            'bio_bn' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('media', 'public');
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $member = CommitteeMember::create($validated);

        activity('committee')
            ->causedBy(Auth::user())
            ->performedOn($member)
            ->withProperties(['name' => $member->name])
            ->log('Created committee member');

        return redirect()->route('dashboard.committee.index')->with('status', __('Committee member created.'));
    }

    public function edit(CommitteeMember $member): View
    {
        return view('dashboard.committee.edit', compact('member'));
    }

    public function update(Request $request, CommitteeMember $member): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'designation' => 'required|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'bio' => 'nullable|string|max:1000',
            'bio_bn' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('photo')) {
            if ($member->photo) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = $request->file('photo')->store('media', 'public');
        }

        $member->update($validated);

        activity('committee')
            ->causedBy(Auth::user())
            ->performedOn($member)
            ->withProperties(['name' => $member->name])
            ->log('Updated committee member');

        return redirect()->route('dashboard.committee.index')->with('status', __('Committee member updated.'));
    }

    public function destroy(CommitteeMember $member): RedirectResponse
    {
        $name = $member->name;
        if ($member->photo) {
            Storage::disk('public')->delete($member->photo);
        }
        $member->delete();

        activity('committee')
            ->causedBy(Auth::user())
            ->log('Deleted committee member: '.$name);

        return redirect()->route('dashboard.committee.index')->with('status', __('Committee member deleted.'));
    }
}
