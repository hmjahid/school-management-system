<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class VehicleController extends Controller
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

        $total = $this->db->count('vehicles');
        $rows = $this->db->fetchAll(
            "SELECT * FROM vehicles ORDER BY number ASC LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.vehicles.index', [
            'rows'     => $rows,
            'vehicles' => $rows,
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
            'number'       => 'required|max:64',
            'type'         => 'max:64',
            'capacity'     => 'numeric',
            'driver_name'  => 'max:191',
            'driver_phone' => 'max:32',
            'is_active'    => 'numeric',
        ]);

        $this->saveVehicle($data);
        Session::getInstance()->flash('success', 'Vehicle added.');
        $this->redirect('/dashboard/vehicles');
    }

    public function saveVehicle(array $data): int
    {
        return $this->db->insert('vehicles', [
            'number'       => $data['number'],
            'type'         => $data['type'] ?? null,
            'capacity'     => $data['capacity'] ?? null,
            'driver_name'  => $data['driver_name'] ?? null,
            'driver_phone' => $data['driver_phone'] ?? null,
            'is_active'    => $data['is_active'] ?? 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.vehicles.create');
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $vehicle = $this->db->fetch("SELECT * FROM vehicles WHERE id = ? LIMIT 1", [$id]);
        if (!$vehicle) {
            Session::getInstance()->flash('error', 'Vehicle not found.');
            $this->redirect('/dashboard/vehicles');
            return;
        }
        $this->view('dashboard.vehicles.edit', ['vehicle' => $vehicle]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $vehicle = $this->db->fetch("SELECT * FROM vehicles WHERE id = ? LIMIT 1", [$id]);
        if (!$vehicle) {
            Session::getInstance()->flash('error', 'Vehicle not found.');
            $this->redirect('/dashboard/vehicles');
            return;
        }

        $data = $this->validate([
            'number'       => 'required|max:64',
            'type'         => 'max:64',
            'capacity'     => 'numeric',
            'driver_name'  => 'max:191',
            'driver_phone' => 'max:32',
            'is_active'    => 'numeric',
        ]);

        $this->db->update('vehicles', [
            'number'       => $data['number'],
            'type'         => $data['type'] ?? null,
            'capacity'     => $data['capacity'] ?? null,
            'driver_name'  => $data['driver_name'] ?? null,
            'driver_phone' => $data['driver_phone'] ?? null,
            'is_active'    => $data['is_active'] ?? 1,
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Vehicle updated.');
        $this->redirect('/dashboard/vehicles');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('vehicles', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Vehicle removed.');
        $this->redirect('/dashboard/vehicles');
    }
}
