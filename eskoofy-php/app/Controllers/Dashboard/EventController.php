<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class EventController extends Controller
{
    private Database $db;

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
            'rows'     => $rows,
            'events' => $rows,
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
            'status'      => 'in:draft,published',
            'image'       => 'max:2048',
        ]);

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

        $this->db->insert('events', [
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

        Session::getInstance()->flash('success', 'Event created successfully.');
        $this->redirect('/dashboard/events');
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
