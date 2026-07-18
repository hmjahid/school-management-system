<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardTransportController extends Controller
{
    // ----- Vehicles -----
    public function vehicles(Request $request): View
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        $rows = Vehicle::orderBy('number')->paginate(20);
        return view('dashboard.transport.vehicles.index', compact('rows'));
    }

    public function vehiclesCreate(Request $request): View
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        return view('dashboard.transport.vehicles.create');
    }

    public function vehiclesStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        $data = $request->validate([
            'number' => ['required', 'string', 'max:64', 'unique:vehicles,number'],
            'type' => ['nullable', 'string', 'max:64'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        Vehicle::create($data + ['is_active' => $data['is_active'] ?? true]);
        return redirect()->route('dashboard.transport.vehicles.index')->with('status', __('Vehicle added.'));
    }

    public function vehiclesEdit(Request $request, Vehicle $vehicle): View
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        return view('dashboard.transport.vehicles.edit', compact('vehicle'));
    }

    public function vehiclesUpdate(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        $data = $request->validate([
            'number' => ['required', 'string', 'max:64', 'unique:vehicles,number,'.$vehicle->id],
            'type' => ['nullable', 'string', 'max:64'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:191'],
            'driver_phone' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $vehicle->update($data);
        return redirect()->route('dashboard.transport.vehicles.index')->with('status', __('Vehicle updated.'));
    }

    public function vehiclesDestroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_vehicles'), 403);
        $vehicle->delete();
        return back()->with('status', __('Vehicle removed.'));
    }

    // ----- Routes -----
    public function routes(Request $request): View
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        $rows = TransportRoute::with(['vehicle', 'stops'])->orderBy('code')->paginate(20);
        return view('dashboard.transport.routes.index', compact('rows'));
    }

    public function routesCreate(Request $request): View
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        return view('dashboard.transport.routes.create', ['vehicles' => Vehicle::where('is_active', true)->orderBy('number')->get()]);
    }

    public function routesStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:32', 'unique:transport_routes,code'],
            'fare' => ['required', 'numeric', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        TransportRoute::create($data + ['is_active' => $data['is_active'] ?? true]);
        return redirect()->route('dashboard.transport.routes.index')->with('status', __('Route added.'));
    }

    public function routesEdit(Request $request, TransportRoute $route): View
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        $route->load('stops');
        return view('dashboard.transport.routes.edit', ['route' => $route, 'vehicles' => Vehicle::where('is_active', true)->orderBy('number')->get()]);
    }

    public function routesUpdate(Request $request, TransportRoute $route): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['required', 'string', 'max:32', 'unique:transport_routes,code,'.$route->id],
            'fare' => ['required', 'numeric', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $route->update($data);

        // Handle stops inline
        if ($request->has('stops')) {
            $existing = $route->stops()->pluck('id')->all();
            $keepIds = [];
            foreach ((array) $request->input('stops', []) as $i => $row) {
                if (empty($row['name'])) {
                    continue;
                }
                $stop = $route->stops()->updateOrCreate(
                    ['id' => $row['id'] ?? null],
                    [
                        'name' => $row['name'],
                        'pickup_time' => !empty($row['pickup_time']) ? $row['pickup_time'] : null,
                        'drop_time' => !empty($row['drop_time']) ? $row['drop_time'] : null,
                        'sort' => (int) ($row['sort'] ?? $i),
                    ],
                );
                $keepIds[] = $stop->id;
            }
            // Delete removed stops
            $route->stops()->whereNotIn('id', $keepIds)->delete();
        }

        return redirect()->route('dashboard.transport.routes.index')->with('status', __('Route updated.'));
    }

    public function routesDestroy(Request $request, TransportRoute $route): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_routes'), 403);
        $route->delete();
        return back()->with('status', __('Route removed.'));
    }

    // ----- Assignments -----
    public function assignments(Request $request): View
    {
        abort_unless($request->user()?->can('assign_vehicles'), 403);
        $rows = TransportAssignment::with(['student.user', 'route', 'stop'])
            ->orderByDesc('effective_from')
            ->paginate(20);
        return view('dashboard.transport.assignments.index', [
            'rows' => $rows,
            'students' => Student::with('user')->orderBy('id')->limit(500)->get(),
            'routes' => TransportRoute::with('stops')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function assignmentsStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('assign_vehicles'), 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'route_id' => ['required', 'integer', 'exists:transport_routes,id'],
            'stop_id' => ['nullable', 'integer', 'exists:transport_stops,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $route = TransportRoute::find($data['route_id']);
        if ($route) {
            $student = Student::find($data['student_id']);
            if ($student) {
                $student->transport_fee = $route->fare;
                $student->save();
            }
        }

        TransportAssignment::create($data);

        return back()->with('status', __('Assignment created; transport fee applied.'));
    }

    public function assignmentsDestroy(Request $request, TransportAssignment $assignment): RedirectResponse
    {
        abort_unless($request->user()?->can('assign_vehicles'), 403);
        $assignment->delete();
        return back()->with('status', __('Assignment removed.'));
    }
}