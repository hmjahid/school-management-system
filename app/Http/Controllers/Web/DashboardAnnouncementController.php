<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $query = Announcement::query()->orderByDesc('starts_at')->orderByDesc('id');

        if ($request->filled('audience')) {
            $query->where('audience', $request->string('audience')->toString());
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('dashboard.announcements.index', compact('rows'));
    }

    public function create(): View
    {
        return view('dashboard.announcements.create', ['announcement' => new Announcement(['is_published' => true, 'audience' => 'all'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $this->validateAnnouncement($request);
        $data['is_published'] = $request->has('is_published');

        $a = Announcement::create($data);

        return redirect()->route('dashboard.announcements.edit', $a)->with('status', __('Announcement saved.'));
    }

    public function edit(Announcement $announcement): View
    {
        return view('dashboard.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $this->validateAnnouncement($request);
        $data['is_published'] = $request->has('is_published');

        $announcement->fill($data)->save();

        return back()->with('status', __('Announcement updated.'));
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $announcement->delete();

        return redirect()->route('dashboard.announcements.index')->with('status', __('Announcement deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'audience' => ['required', 'in:all,student,parent'],
            'is_published' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
