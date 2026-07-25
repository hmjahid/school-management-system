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
        $year = $anchor->year;

        $events = Event::query()
            ->whereBetween('start_date', [$start, $end])
            ->orderBy('start_date')
            ->get();

        $byDay = [];
        foreach ($events as $e) {
            $key = $e->start_date->format('Y-m-d');
            $byDay[$key][] = ['type' => 'event', 'title' => $e->title, 'model' => $e];
        }

        $holidays = $this->getGovernmentHolidays($year);
        foreach ($holidays as $h) {
            $date = Carbon::parse($h['date']);
            if ($date->between($start, $end)) {
                $key = $date->format('Y-m-d');
                $byDay[$key][] = ['type' => 'holiday', 'title' => $h['name']];
            }
        }

        $academicItems = $this->getAcademicActivities($start, $end, $year);
        foreach ($academicItems as $a) {
            $date = Carbon::parse($a['date']);
            $key = $date->format('Y-m-d');
            $byDay[$key][] = ['type' => 'academic', 'title' => $a['title']];
        }

        $schoolItems = $this->getSchoolActivities($start, $end);
        foreach ($schoolItems as $s) {
            $date = Carbon::parse($s['date']);
            $key = $date->format('Y-m-d');
            $byDay[$key][] = ['type' => 'school', 'title' => $s['title']];
        }

        return view('dashboard.events.calendar', [
            'anchor' => $anchor,
            'start' => $start,
            'end' => $end,
            'byDay' => $byDay,
            'holidays' => $holidays,
            'year' => $year,
        ]);
    }

    private function getGovernmentHolidays(int $year): array
    {
        return [
            ['date' => "{$year}-02-21", 'name' => 'International Mother Language Day'],
            ['date' => "{$year}-03-26", 'name' => 'Independence Day'],
            ['date' => "{$year}-04-14", 'name' => 'Bangla New Year (Pohela Boishakh)'],
            ['date' => "{$year}-04-21", 'name' => 'Shab-e-Barat'],
            ['date' => "{$year}-05-01", 'name' => 'May Day (Labour Day)'],
            ['date' => "{$year}-05-23", 'name' => 'Buddha Purnima'],
            ['date' => "{$year}-06-17", 'name' => 'Eid ul-Adha'],
            ['date' => "{$year}-06-18", 'name' => 'Eid ul-Adha Holiday'],
            ['date' => "{$year}-06-19", 'name' => 'Eid ul-Adha Holiday'],
            ['date' => "{$year}-07-15", 'name' => 'Shab-e-Qadr'],
            ['date' => "{$year}-07-16", 'name' => 'Jumatul Bidah'],
            ['date' => "{$year}-07-17", 'name' => 'Eid ul-Fitr'],
            ['date' => "{$year}-07-18", 'name' => 'Eid ul-Fitr Holiday'],
            ['date' => "{$year}-07-19", 'name' => 'Eid ul-Fitr Holiday'],
            ['date' => "{$year}-08-15", 'name' => 'National Mourning Day'],
            ['date' => "{$year}-09-05", 'name' => 'Janmashtami'],
            ['date' => "{$year}-10-02", 'name' => 'Eid-e-Milad-un-Nabi'],
            ['date' => "{$year}-12-16", 'name' => 'Victory Day'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day'],
            ['date' => "{$year}-12-31", 'name' => 'New Year\'s Eve (Bank Holiday)'],
        ];
    }

    private function getAcademicActivities(Carbon $start, Carbon $end, int $year): array
    {
        $items = [];

        $examPeriods = [
            ['start' => "{$year}-02-01", 'end' => "{$year}-02-15", 'title' => 'Half-Yearly Exams'],
            ['start' => "{$year}-02-20", 'end' => "{$year}-02-28", 'title' => 'Results Publication'],
            ['start' => "{$year}-04-01", 'end' => "{$year}-04-10", 'title' => 'Class Test'],
            ['start' => "{$year}-06-01", 'end' => "{$year}-06-15", 'title' => 'Annual Exams Begin'],
            ['start' => "{$year}-06-25", 'end' => "{$year}-07-05", 'title' => 'Annual Results'],
            ['start' => "{$year}-09-01", 'end' => "{$year}-09-15", 'title' => 'Mid-Term Exams'],
            ['start' => "{$year}-09-20", 'end' => "{$year}-09-30", 'title' => 'Mid-Term Results'],
            ['start' => "{$year}-11-15", 'end' => "{$year}-11-30", 'title' => 'Pre-Final Exams'],
            ['start' => "{$year}-12-05", 'end' => "{$year}-12-15", 'title' => 'Final Results'],
        ];

        foreach ($examPeriods as $ep) {
            $epStart = Carbon::parse($ep['start']);
            $epEnd = Carbon::parse($ep['end']);
            if ($epStart->lte($end) && $epEnd->gte($start)) {
                $items[] = ['date' => $ep['start'], 'title' => $ep['title']];
            }
        }

        $termStarts = [
            ['date' => "{$year}-01-02", 'title' => 'Winter Term Begins'],
            ['date' => "{$year}-04-15", 'title' => 'Summer Term Begins'],
            ['date' => "{$year}-09-01", 'title' => 'Autumn Term Begins'],
        ];

        foreach ($termStarts as $ts) {
            $tsDate = Carbon::parse($ts['date']);
            if ($tsDate->between($start, $end)) {
                $items[] = $ts;
            }
        }

        return $items;
    }

    private function getSchoolActivities(Carbon $start, Carbon $end): array
    {
        $items = [];
        $year = $start->year;

        $activities = [
            ['date' => "{$year}-01-15", 'title' => 'Annual Sports Day'],
            ['date' => "{$year}-02-21", 'title' => 'Language Day Assembly'],
            ['date' => "{$year}-03-17", 'title' => 'Science Fair'],
            ['date' => "{$year}-03-26", 'title' => 'Independence Day Program'],
            ['date' => "{$year}-04-14", 'title' => 'Cultural Program (Pohela Boishakh)'],
            ['date' => "{$year}-05-01", 'title' => 'Workers\' Day Assembly'],
            ['date' => "{$year}-06-05", 'title' => 'World Environment Day'],
            ['date' => "{$year}-08-15", 'title' => 'Mourning Day Assembly'],
            ['date' => "{$year}-09-08", 'title' => 'Teachers\' Day'],
            ['date' => "{$year}-10-16", 'title' => 'World Food Day'],
            ['date' => "{$year}-10-31", 'title' => 'Annual Cultural Program'],
            ['date' => "{$year}-11-01", 'title' => 'Parents\' Day'],
            ['date' => "{$year}-12-02", 'title' => 'Sports Tournament'],
            ['date' => "{$year}-12-16", 'title' => 'Victory Day Assembly'],
        ];

        foreach ($activities as $a) {
            $aDate = Carbon::parse($a['date']);
            if ($aDate->between($start, $end)) {
                $items[] = $a;
            }
        }

        return $items;
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
