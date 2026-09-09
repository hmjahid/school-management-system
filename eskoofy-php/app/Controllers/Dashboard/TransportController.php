<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class TransportController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function routes(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('transport_routes');
        $rows = $this->db->fetchAll(
            "SELECT tr.*, v.number as vehicle_number, v.driver_name
             FROM transport_routes tr
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             ORDER BY tr.name ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $vehicles = $this->db->fetchAll(
            "SELECT id, number FROM vehicles WHERE is_active = 1 ORDER BY number ASC"
        );

        $this->view('dashboard.transport.routes', [
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'vehicles'  => $vehicles,
        ]);
    }

    public function storeRoute(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'       => 'required|max:191',
            'code'       => 'required|max:32',
            'fare'       => 'required|numeric',
            'vehicle_id' => 'numeric',
            'is_active'  => 'numeric',
        ]);

        $this->db->insert('transport_routes', [
            'name'       => $data['name'],
            'code'       => $data['code'],
            'fare'       => $data['fare'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'is_active'  => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Route added.');
        $this->redirect('/dashboard/transport-routes');
    }

    public function assignments(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('transport_assignments');
        $rows = $this->db->fetchAll(
            "SELECT ta.*, u.name as student_name, s.admission_number, tr.name as route_name, tr.fare
             FROM transport_assignments ta
             LEFT JOIN students st ON ta.student_id = st.id
             LEFT JOIN users u ON st.user_id = u.id
             LEFT JOIN transport_routes tr ON ta.route_id = tr.id
             ORDER BY ta.effective_from DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $students = $this->db->fetchAll(
            "SELECT s.id, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.status = 'active' ORDER BY u.name ASC LIMIT 500"
        );
        $routes = $this->db->fetchAll(
            "SELECT id, name, fare FROM transport_routes WHERE is_active = 1 ORDER BY name ASC"
        );

        $this->view('dashboard.transport.assignments', [
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'students'  => $students,
            'routes'    => $routes,
        ]);
    }

    public function storeAssignment(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'student_id'     => 'required|numeric',
            'route_id'       => 'required|numeric',
            'effective_from' => 'required',
            'effective_to'   => '',
        ]);

        $route = $this->db->fetch("SELECT * FROM transport_routes WHERE id = ? LIMIT 1", [$data['route_id']]);
        if ($route) {
            $this->db->update('students', [
                'transport_fee' => $route['fare'],
                'updated_at'    => date('Y-m-d H:i:s'),
            ], 'id = ?', [$data['student_id']]);
        }

        $this->db->insert('transport_assignments', [
            'student_id'     => $data['student_id'],
            'route_id'       => $data['route_id'],
            'effective_from' => $data['effective_from'],
            'effective_to'   => $data['effective_to'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Assignment created; transport fee applied.');
        $this->redirect('/dashboard/transport-assignments');
    }
}
