<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class TransportController extends Controller
{
    private DatabaseInterface $db;

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
            "SELECT ta.*, u.name as student_name, st.admission_number, tr.name as route_name, tr.fare
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

    public function destroyAssignment(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('transport_assignments', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Assignment removed.');
        $this->redirect('/dashboard/transport-assignments');
    }

    public function editRoute(int $id): void
    {
        Auth::requireAuth();
        $route = $this->db->fetch(
            "SELECT tr.*, v.number as vehicle_number FROM transport_routes tr
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id WHERE tr.id = ? LIMIT 1",
            [$id]
        );
        if (!$route) {
            Session::getInstance()->flash('error', 'Route not found.');
            $this->redirect('/dashboard/transport-routes');
            return;
        }
        $stops = $this->db->fetchAll(
            "SELECT * FROM transport_stops WHERE route_id = ? ORDER BY sort ASC, id ASC", [$id]
        );
        $vehicles = $this->db->fetchAll(
            "SELECT id, number FROM vehicles WHERE is_active = 1 ORDER BY number ASC"
        );

        $this->view('dashboard.transport.route_edit', [
            'route'    => $route,
            'stops'    => $stops,
            'vehicles' => $vehicles,
        ]);
    }

    public function updateRoute(int $id): void
    {
        Auth::requireAuth();
        $route = $this->db->fetch("SELECT * FROM transport_routes WHERE id = ? LIMIT 1", [$id]);
        if (!$route) {
            Session::getInstance()->flash('error', 'Route not found.');
            $this->redirect('/dashboard/transport-routes');
            return;
        }

        $data = $this->validate([
            'name'       => 'required|max:191',
            'code'       => 'required|max:32',
            'fare'       => 'required|numeric',
            'vehicle_id' => 'numeric',
            'is_active'  => 'numeric',
        ]);

        $this->db->update('transport_routes', [
            'name'       => $data['name'],
            'code'       => $data['code'],
            'fare'       => $data['fare'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'is_active'  => $data['is_active'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        // Inline stops sync.
        $kept = [];
        $stops = $_POST['stops'] ?? [];
        $sort = 0;
        foreach ($stops as $stop) {
            if (empty($stop['name'])) {
                continue;
            }
            $pickup = !empty($stop['pickup_time']) ? $stop['pickup_time'] : null;
            $drop = !empty($stop['drop_time']) ? $stop['drop_time'] : null;
            $stopId = !empty($stop['id']) ? (int) $stop['id'] : 0;
            $sort++;
            if ($stopId > 0) {
                $this->db->update('transport_stops', [
                    'name'       => $stop['name'],
                    'pickup_time'=> $pickup,
                    'drop_time'  => $drop,
                    'sort'       => $sort,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$stopId]);
                $kept[] = $stopId;
            } else {
                $kept[] = $this->db->insert('transport_stops', [
                    'route_id'    => $id,
                    'name'        => $stop['name'],
                    'pickup_time' => $pickup,
                    'drop_time'   => $drop,
                    'sort'        => $sort,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
        if (!empty($kept)) {
            $in = implode(',', array_map('intval', $kept));
            $this->db->delete('transport_stops', "route_id = ? AND id NOT IN ({$in})", [$id]);
        }

        Session::getInstance()->flash('success', 'Route updated.');
        $this->redirect('/dashboard/transport-routes');
    }

    public function destroyRoute(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('transport_stops', 'route_id = ?', [$id]);
        $this->db->delete('transport_routes', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Route removed.');
        $this->redirect('/dashboard/transport-routes');
    }
}
