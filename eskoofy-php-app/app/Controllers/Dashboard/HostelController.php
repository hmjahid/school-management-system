<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class HostelController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('hostels');
        $rows = $this->db->fetchAll(
            "SELECT h.*,
                (SELECT COUNT(*) FROM hostel_rooms WHERE hostel_id = h.id) as rooms_count
             FROM hostels h
             ORDER BY h.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.hostels.index', [
            'rows'     => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Hostel::class),
            'hostels' => $this->paginateRows($rows, $total, $perPage, $page, \App\Models\Hostel::class),
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'         => 'required|max:255',
            'address'      => 'max:500',
            'description'  => 'max:1000',
            'total_rooms'  => 'numeric',
            'warden_name'  => 'max:255',
            'warden_phone' => 'max:50',
            'status'       => 'in:active,inactive',
        ]);

        $this->db->insert('hostels', [
            'name'         => $data['name'],
            'address'      => $data['address'] ?? null,
            'description'  => $data['description'] ?? null,
            'total_rooms'  => $data['total_rooms'] ?? null,
            'warden_name'  => $data['warden_name'] ?? null,
            'warden_phone' => $data['warden_phone'] ?? null,
            'status'       => $data['status'] ?? 'active',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Hostel created.');
        $this->redirect('/dashboard/hostels');
    }

    public function rooms(): void
    {
        Auth::requireAuth();
        $hostelId = (int) ($_GET['hostel_id'] ?? 0);
        $where = '1=1';
        $params = [];
        if ($hostelId > 0) {
            $where .= ' AND hr.hostel_id = ?';
            $params[] = $hostelId;
        }

        $rows = $this->db->fetchAll(
            "SELECT hr.*, h.name as hostel_name
             FROM hostel_rooms hr
             LEFT JOIN hostels h ON hr.hostel_id = h.id
             WHERE {$where}
             ORDER BY h.name ASC, hr.room_number ASC",
            $params
        );

        $hostels = $this->db->fetchAll("SELECT id, name FROM hostels WHERE status = 'active' ORDER BY name ASC");

        $this->view('dashboard.hostels.rooms', [
            'rows'    => $rows,
            'hostels' => $hostels,
            'hostelId'=> $hostelId,
        ]);
    }

    public function storeRoom(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'hostel_id'   => 'required|numeric',
            'room_number' => 'required|max:50',
            'room_type'   => 'max:100',
            'capacity'    => 'required|numeric',
            'status'      => 'in:available,occupied,maintenance',
        ]);

        $this->db->insert('hostel_rooms', [
            'hostel_id'   => $data['hostel_id'],
            'room_number' => $data['room_number'],
            'room_type'   => $data['room_type'] ?? null,
            'capacity'    => $data['capacity'],
            'occupied'    => 0,
            'status'      => $data['status'] ?? 'available',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Room added.');
        $this->redirect("/dashboard/hostel-rooms?hostel_id={$data['hostel_id']}");
    }

    public function storeAssignment(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'hostel_id'     => 'required|numeric',
            'room_id'       => 'required|numeric',
            'student_id'    => 'required|numeric',
            'check_in_date' => 'required',
            'check_out_date'=> '',
            'notes'         => 'max:1000',
        ]);

        $room = $this->db->fetch("SELECT * FROM hostel_rooms WHERE id = ? LIMIT 1", [$data['room_id']]);
        if (!$room) {
            Session::getInstance()->flash('error', 'Room not found.');
            $this->back();
            return;
        }
        if ($room['occupied'] >= $room['capacity']) {
            Session::getInstance()->flash('error', 'This room is already full.');
            $this->back();
            return;
        }

        $this->db->insert('hostel_assignments', [
            'hostel_id'     => $data['hostel_id'],
            'room_id'       => $data['room_id'],
            'student_id'    => $data['student_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date'=> $data['check_out_date'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->db->query("UPDATE hostel_rooms SET occupied = occupied + 1 WHERE id = ?", [$data['room_id']]);

        Session::getInstance()->flash('success', 'Student assigned to room.');
        $this->redirect("/dashboard/hostels/{$data['hostel_id']}");
    }

    // ----- Dashboard.hostels.* (dashboard/hostels/...) -----

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.hostels.create', ['hostel' => new \App\Models\Hostel()]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $hostel = \App\Models\Hostel::find($id);
        if (!$hostel) {
            Session::getInstance()->flash('error', 'Hostel not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }
        $rooms = $this->db->fetchAll(
            "SELECT * FROM hostel_rooms WHERE hostel_id = ? ORDER BY room_number ASC", [$id]
        );
        $hostel->setRelation('rooms', new \App\Core\Support\Collection(\App\Models\HostelRoom::hydrate($rooms)));

        $this->view('dashboard.hostels.show', [
            'hostel'   => $hostel,
            'students' => $this->activeStudents(),
        ]);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $hostel = \App\Models\Hostel::find($id);
        if (!$hostel) {
            Session::getInstance()->flash('error', 'Hostel not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }
        $this->view('dashboard.hostels.edit', ['hostel' => $hostel]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $hostel = \App\Models\Hostel::find($id);
        if (!$hostel) {
            Session::getInstance()->flash('error', 'Hostel not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }

        $data = $this->validate([
            'name'         => 'required|max:255',
            'address'      => 'max:500',
            'description'  => 'max:1000',
            'total_rooms'  => 'numeric',
            'warden_name'  => 'max:255',
            'warden_phone' => 'max:50',
            'status'       => 'in:active,inactive',
        ]);

        $this->db->update('hostels', [
            'name'         => $data['name'],
            'address'      => $data['address'] ?? null,
            'description'  => $data['description'] ?? null,
            'total_rooms'  => $data['total_rooms'] ?? null,
            'warden_name'  => $data['warden_name'] ?? null,
            'warden_phone' => $data['warden_phone'] ?? null,
            'status'       => $data['status'] ?? 'active',
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Hostel updated.');
        $this->redirect("/dashboard/hostels/{$id}");
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('hostel_assignments', 'hostel_id = ?', [$id]);
        $this->db->delete('hostel_rooms', 'hostel_id = ?', [$id]);
        $this->db->delete('hostels', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Hostel deleted.');
        $this->redirect('/dashboard/hostels');
    }

    public function storeRoomForHostel(int $hostelId): void
    {
        Auth::requireAuth();
        $hostel = \App\Models\Hostel::find($hostelId);
        if (!$hostel) {
            Session::getInstance()->flash('error', 'Hostel not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }

        $data = $this->validate([
            'room_number' => 'required|max:50',
            'room_type'   => 'max:100',
            'capacity'    => 'required|numeric',
            'status'      => 'in:available,occupied,maintenance',
        ]);

        $this->db->insert('hostel_rooms', [
            'hostel_id'   => $hostelId,
            'room_number' => $data['room_number'],
            'room_type'   => $data['room_type'] ?? null,
            'capacity'    => $data['capacity'],
            'occupied'    => 0,
            'status'      => $data['status'] ?? 'available',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Room added.');
        $this->redirect("/dashboard/hostels/{$hostelId}");
    }

    public function updateRoom(int $id): void
    {
        Auth::requireAuth();
        $room = \App\Models\HostelRoom::find($id);
        if (!$room) {
            Session::getInstance()->flash('error', 'Room not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }

        $data = $this->validate([
            'room_number' => 'required|max:50',
            'room_type'   => 'max:100',
            'capacity'    => 'required|numeric',
            'status'      => 'in:available,occupied,maintenance',
        ]);

        $this->db->update('hostel_rooms', [
            'room_number' => $data['room_number'],
            'room_type'   => $data['room_type'] ?? null,
            'capacity'    => $data['capacity'],
            'status'      => $data['status'] ?? 'available',
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Room updated.');
        $this->redirect("/dashboard/hostels/{$room->hostel_id}");
    }

    public function destroyRoom(int $id): void
    {
        Auth::requireAuth();
        $room = \App\Models\HostelRoom::find($id);
        $hostelId = $room?->hostel_id;
        if ($room) {
            $this->db->delete('hostel_assignments', 'room_id = ?', [$id]);
            $this->db->delete('hostel_rooms', 'id = ?', [$id]);
        }
        Session::getInstance()->flash('success', 'Room deleted.');
        $this->redirect($hostelId ? "/dashboard/hostels/{$hostelId}" : '/dashboard/hostels');
    }

    public function storeAssignmentForHostel(int $hostelId): void
    {
        Auth::requireAuth();
        $hostel = \App\Models\Hostel::find($hostelId);
        if (!$hostel) {
            Session::getInstance()->flash('error', 'Hostel not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }

        $data = $this->validate([
            'room_id'       => 'required|numeric',
            'student_id'    => 'required|numeric',
            'check_in_date' => 'required',
            'check_out_date'=> '',
            'notes'         => 'max:1000',
        ]);

        $room = $this->db->fetch("SELECT * FROM hostel_rooms WHERE id = ? LIMIT 1", [$data['room_id']]);
        if (!$room) {
            Session::getInstance()->flash('error', 'Room not found.');
            $this->redirect("/dashboard/hostels/{$hostelId}");
            return;
        }
        if ($room['occupied'] >= $room['capacity']) {
            Session::getInstance()->flash('error', 'This room is already full.');
            $this->redirect("/dashboard/hostels/{$hostelId}");
            return;
        }

        $this->db->insert('hostel_assignments', [
            'hostel_id'     => $hostelId,
            'room_id'       => $data['room_id'],
            'student_id'    => $data['student_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date'=> $data['check_out_date'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->db->query("UPDATE hostel_rooms SET occupied = occupied + 1 WHERE id = ?", [$data['room_id']]);

        Session::getInstance()->flash('success', 'Student assigned to room.');
        $this->redirect("/dashboard/hostels/{$hostelId}");
    }

    public function destroyAssignment(int $id): void
    {
        Auth::requireAuth();
        $assignment = $this->db->fetch("SELECT * FROM hostel_assignments WHERE id = ? LIMIT 1", [$id]);
        if (!$assignment) {
            Session::getInstance()->flash('error', 'Assignment not found.');
            $this->redirect('/dashboard/hostels');
            return;
        }

        $this->db->delete('hostel_assignments', 'id = ?', [$id]);

        $room = $this->db->fetch("SELECT * FROM hostel_rooms WHERE id = ? LIMIT 1", [$assignment['room_id']]);
        if ($room && (int) $room['occupied'] > 0) {
            $this->db->query("UPDATE hostel_rooms SET occupied = occupied - 1 WHERE id = ?", [$assignment['room_id']]);
        }

        Session::getInstance()->flash('success', 'Assignment removed.');
        $this->redirect("/dashboard/hostels/{$assignment['hostel_id']}");
    }

    private function activeStudents(int $limit = 500): \App\Core\Support\Collection
    {
        $rows = $this->db->fetchAll(
            "SELECT s.*, u.name AS user_name, u.email AS user_email
             FROM students s JOIN users u ON s.user_id = u.id
             WHERE s.status = 'active'
             ORDER BY u.name ASC LIMIT {$limit}"
        );
        $students = \App\Models\Student::hydrate($rows);
        foreach ($students as $student) {
            $student->setRelation('user', \App\Models\User::newFromRow([
                'id'    => $student->user_id,
                'name'  => $student->user_name,
                'email' => $student->user_email,
            ]));
        }
        return new \App\Core\Support\Collection($students);
    }
}
