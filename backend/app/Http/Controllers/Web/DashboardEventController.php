<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardEventController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::query()->latest('start_date');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $events = $query->paginate(20)->withQueryString();

        return view('dashboard.events.index', [
            'events' => $events,
            'statuses' => ['draft', 'published', 'cancelled', 'completed'],
        ]);
    }

    public function calendar(Request $request): View
    {
        $month = $request->string('month')->toString() ?: now()->format('Y-m');
        try {
            $anchor = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $anchor = now()->startOfMonth();
        }

        $start = $anchor->copy()->startOfWeek();
        $end = $anchor->copy()->endOfMonth()->endOfWeek();

        $events = Event::query()
            ->whereBetween('start_date', [$start, $end])
            ->orderBy('start_date')
            ->get();

        $byDay = [];
        foreach ($events as $e) {
            $key = $e->start_date->format('Y-m-d');
            $byDay[$key][] = $e;
        }

        return view('dashboard.events.calendar', [
            'anchor' => $anchor,
            'start' => $start,
            'end' => $end,
            'byDay' => $byDay,
        ]);
    }

    public function create(): View
    {
        return view('dashboard.events.create', ['event' => new Event()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        $event = Event::create($data);

        return redirect()->route('dashboard.events')->with('status', __('Event created.'));
    }

    public function edit(Event $event): View
    {
        return view('dashboard.events.edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validated($request);
        $event->update($data);

        return redirect()->route('dashboard.events')->with('status', __('Event updated.'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('dashboard.events')->with('status', __('Event deleted.'));
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:200',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_deadline' => 'nullable|date',
            'max_attendees' => 'nullable|integer|min:1',
            'is_virtual' => 'sometimes|boolean',
            'meeting_url' => 'nullable|url|max:500',
            'status' => 'required|in:draft,published,cancelled,completed',
            'image' => 'nullable|string|max:500',
        ]);
    }
}
