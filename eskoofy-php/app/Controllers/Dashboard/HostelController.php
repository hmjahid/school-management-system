<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class HostelController extends Controller
{
    private Database $db;

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
                (SELECT COUNT(*) FROM hostel_rooms WHERE hostel_id = h.id) as room_count
             FROM hostels h
             ORDER BY h.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.hostels.index', [
            'rows'     => $rows,
            'hostels' => $rows,
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
}
