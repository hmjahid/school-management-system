<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class EventController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (e.title LIKE ? OR e.description LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND e.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM events e WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT e.*, u.name as creator_name
             FROM events e
             LEFT JOIN users u ON e.created_by = u.id
             WHERE {$where}
             ORDER BY e.start_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.events.index', [
            'rows'     => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Event::class),
            'events' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Event::class),
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'status'   => $status,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:255',
            'description' => 'max:5000',
            'start_date'  => 'required',
            'end_date'    => '',
            'location'    => 'max:255',
            'status'      => 'in:draft,published,cancelled,completed',
            'image'       => 'max:2048',
        ]);

        $this->storeOne($data);

        Session::getInstance()->flash('success', 'Event created.');
        $this->redirect('/dashboard/events');
    }

    public function storeOne(array $data): int
    {
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'event-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $imagePath = 'uploads/events/' . $filename;
        }

        return $this->db->insert('events', [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'] ?? null,
            'location'    => $data['location'] ?? null,
            'status'      => $data['status'] ?? 'draft',
            'image'       => $imagePath,
            'created_by'  => Auth::id(),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.events.create');
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $event = $this->db->fetch("SELECT * FROM events WHERE id = ? LIMIT 1", [$id]);
        if (!$event) {
            Session::getInstance()->flash('error', 'Event not found.');
            $this->redirect('/dashboard/events');
            return;
        }
        $this->view('dashboard.events.edit', ['event' => $event]);
    }

    public function calendar(): void
    {
        Auth::requireAuth();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            $month = date('Y-m');
        }

        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $anchor = \App\Core\Support\Carbon::create($year, $monthNum, 1);

        // Week starts Sunday.
        $start = $anchor->copy()->startOfDay();
        if ($start->format('w') !== '0') {
            $start = \App\Core\Support\Carbon::parse(date('Y-m-d', strtotime('last sunday', $anchor->timestamp())));
        }
        $lastDay = \App\Core\Support\Carbon::create($year, $monthNum, (int) $anchor->copy()->endOfMonth()->format('j'));
        $end = \App\Core\Support\Carbon::parse(date('Y-m-d', strtotime('next saturday', $lastDay->timestamp())));

        $rows = $this->db->fetchAll(
            "SELECT * FROM events WHERE start_date BETWEEN ? AND ? ORDER BY start_date ASC",
            [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d 23:59:59')]
        );
        $eventModels = \App\Models\Event::hydrate($rows);

        $byDay = [];
        foreach ($eventModels as $model) {
            $key = $model->start_date?->format('Y-m-d') ?? date('Y-m-d', strtotime((string) ($model->getAttributes()['start_date'] ?? '')));
            $byDay[$key][] = ['type' => 'event', 'title' => $model->title, 'id' => $model->id, 'model' => $model];
        }

        $holidays = $this->governmentHolidays($year);
        foreach ($holidays as $h) {
            $hDate = $h['date'];
            if ($hDate >= $start->toDateString() && $hDate <= $end->toDateString()) {
                $byDay[$hDate][] = ['type' => 'holiday', 'title' => $h['name']];
            }
        }

        $academic = $this->academicActivities($year);
        foreach ($academic as $a) {
            $aDate = $year . '-' . $a['date'];
            if ($aDate >= $start->toDateString() && $aDate <= $end->toDateString()) {
                $byDay[$aDate][] = ['type' => 'academic', 'title' => $a['name']];
            }
        }

        $school = $this->schoolActivities($year);
        foreach ($school as $s) {
            $sDate = $year . '-' . $s['date'];
            if ($sDate >= $start->toDateString() && $sDate <= $end->toDateString()) {
                $byDay[$sDate][] = ['type' => 'school', 'title' => $s['name']];
            }
        }

        ksort($byDay);

        $upcomingHolidays = [];
        foreach ($holidays as $h) {
            $d = $h['date'];
            if ($d >= date('Y-m-d')) {
                $upcomingHolidays[] = ['date' => $d, 'name' => $h['name']];
            }
            if (count($upcomingHolidays) >= 5) {
                break;
            }
        }

        $upcomingEvents = $this->db->fetchAll(
            "SELECT id, title, start_date FROM events WHERE start_date >= NOW() ORDER BY start_date ASC LIMIT 5"
        );

        $this->view('dashboard.events.calendar', [
            'anchor'          => $anchor,
            'month'           => $month,
            'year'            => $year,
            'start'           => $start,
            'end'             => $end,
            'byDay'           => $byDay,
            'upcomingHolidays'=> $upcomingHolidays,
            'upcomingEvents'  => $upcomingEvents,
            'holidays'        => $holidays,
        ]);
    }

    private function governmentHolidays(int $year): array
    {
        $dates = [
            ['02-21', 'International Mother Language Day'], ['03-26', 'Independence Day'],
            ['04-14', 'Bangla New Year (Pohela Boishakh)'], ['04-21', 'Shab-e-Barat'],
            ['05-01', 'May Day (Labour Day)'], ['05-23', 'Buddha Purnima'],
            ['06-17', 'Eid ul-Adha'], ['06-18', 'Eid ul-Adha Holiday'], ['06-19', 'Eid ul-Adha Holiday'],
            ['07-15', 'Shab-e-Qadr'], ['07-16', 'Jumatul Bidah'],
            ['07-17', 'Eid ul-Fitr'], ['07-18', 'Eid ul-Fitr Holiday'], ['07-19', 'Eid ul-Fitr Holiday'],
            ['08-15', 'National Mourning Day'], ['09-05', 'Janmashtami'],
            ['10-02', 'Eid-e-Milad-un-Nabi'], ['12-16', 'Victory Day'],
            ['12-25', 'Christmas Day'], ['12-31', "New Year's Eve (Bank Holiday)"],
        ];
        $out = [];
        foreach ($dates as $d) {
            $out[] = ['date' => "{$year}-{$d[0]}", 'name' => $d[1]];
        }
        return $out;
    }

    private function academicActivities(int $year): array
    {
        $periods = [
            ['02-01', '02-15', 'Half-Yearly Exams'], ['02-20', '02-28', 'Results Publication'],
            ['04-01', '04-10', 'Class Test'], ['06-01', '06-15', 'Annual Exams Begin'],
            ['06-25', '07-05', 'Annual Results'], ['09-01', '09-15', 'Mid-Term Exams'],
            ['09-20', '09-30', 'Mid-Term Results'], ['11-15', '11-30', 'Pre-Final Exams'],
            ['12-05', '12-15', 'Final Results'],
        ];
        $terms = [
            ['01-02', 'Winter Term Begins'], ['04-15', 'Summer Term Begins'], ['09-01', 'Autumn Term Begins'],
        ];
        $out = [];
        foreach ($periods as $p) {
            $out[] = ['date' => $p[0], 'name' => $p[2]];
        }
        foreach ($terms as $t) {
            $out[] = ['date' => $t[0], 'name' => $t[1]];
        }
        return $out;
    }

    private function schoolActivities(int $year): array
    {
        $dates = [
            ['01-15', 'Annual Sports Day'], ['02-21', 'Language Day Assembly'],
            ['03-17', 'Science Fair'], ['03-26', 'Independence Day Program'],
            ['04-14', 'Cultural Program (Pohela Boishakh)'], ['05-01', "Workers' Day Assembly"],
            ['06-05', 'World Environment Day'], ['08-15', 'Mourning Day Assembly'],
            ['09-08', "Teachers' Day"], ['10-16', 'World Food Day'],
            ['10-31', 'Annual Cultural Program'], ['11-01', "Parents' Day"],
            ['12-02', 'Sports Tournament'], ['12-16', 'Victory Day Assembly'],
        ];
        $out = [];
        foreach ($dates as $d) {
            $out[] = ['date' => $d[0], 'name' => $d[1]];
        }
        return $out;
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $event = $this->db->fetch("SELECT * FROM events WHERE id = ? LIMIT 1", [$id]);
        if (!$event) {
            Session::getInstance()->flash('error', 'Event not found.');
            $this->redirect('/dashboard/events');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:255',
            'description' => 'max:5000',
            'start_date'  => 'required',
            'end_date'    => '',
            'location'    => 'max:255',
            'status'      => 'in:draft,published',
        ]);

        $updateData = [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'] ?? null,
            'location'    => $data['location'] ?? null,
            'status'      => $data['status'] ?? 'draft',
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'event-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $updateData['image'] = 'uploads/events/' . $filename;
        }

        $this->db->update('events', $updateData, 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Event updated.');
        $this->redirect('/dashboard/events');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('events', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Event deleted.');
        $this->redirect('/dashboard/events');
    }
}
