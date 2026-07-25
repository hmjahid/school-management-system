<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardHostelController extends Controller
{
    public function index(): View
    {
        $hostels = Hostel::withCount('rooms')->orderByDesc('id')->paginate(20);

        return view('dashboard.hostels.index', compact('hostels'));
    }

    public function show(Hostel $hostel): View
    {
        $hostel->load(['rooms' => function ($q) {
            $q->withCount('assignments');
        }]);

        $students = Student::with('user')->orderBy('id')->get();

        return view('dashboard.hostels.show', compact('hostel', 'students'));
    }

    public function create(): View
    {
        return view('dashboard.hostels.create', [
            'hostel' => new Hostel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'total_rooms' => ['nullable', 'integer', 'min:0'],
            'warden_name' => ['nullable', 'string', 'max:255'],
            'warden_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        Hostel::create($data);

        return redirect()->route('dashboard.hostels.index')->with('status', __('Hostel created.'));
    }

    public function edit(Hostel $hostel): View
    {
        return view('dashboard.hostels.edit', compact('hostel'));
    }

    public function update(Request $request, Hostel $hostel): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'total_rooms' => ['nullable', 'integer', 'min:0'],
            'warden_name' => ['nullable', 'string', 'max:255'],
            'warden_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $hostel->fill($data)->save();

        return back()->with('status', __('Hostel updated.'));
    }

    public function destroy(Hostel $hostel): RedirectResponse
    {
        $hostel->delete();

        return redirect()->route('dashboard.hostels.index')->with('status', __('Hostel deleted.'));
    }

    public function storeRoom(Request $request, Hostel $hostel): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:50'],
            'room_type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:available,occupied,maintenance'],
        ]);

        $data['hostel_id'] = $hostel->id;
        $data['occupied'] = 0;

        HostelRoom::create($data);

        return back()->with('status', __('Room added.'));
    }

    public function updateRoom(Request $request, HostelRoom $room): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:50'],
            'room_type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:available,occupied,maintenance'],
        ]);

        $room->fill($data)->save();

        return back()->with('status', __('Room updated.'));
    }

    public function destroyRoom(HostelRoom $room): RedirectResponse
    {
        $hostelId = $room->hostel_id;
        $room->delete();

        return redirect()->route('dashboard.hostels.show', $hostelId)->with('status', __('Room deleted.'));
    }

    public function storeAssignment(Request $request, Hostel $hostel): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'room_id' => ['required', 'exists:hostel_rooms,id'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['nullable', 'date', 'after_or_equal:check_in_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['status'] = 'active';

        $room = HostelRoom::findOrFail($data['room_id']);
        if ($room->occupied >= $room->capacity) {
            return back()->withErrors(['room_id' => __('This room is already full.')]);
        }

        HostelAssignment::create($data);
        $room->increment('occupied');

        return back()->with('status', __('Student assigned to room.'));
    }

    public function destroyAssignment(HostelAssignment $assignment): RedirectResponse
    {
        $room = $assignment->room;
        $assignment->delete();

        if ($room && $room->occupied > 0) {
            $room->decrement('occupied');
        }

        $hostelId = $room?->hostel_id;

        return $hostelId
            ? redirect()->route('dashboard.hostels.show', $hostelId)->with('status', __('Assignment removed.'))
            : redirect()->route('dashboard.hostels.index')->with('status', __('Assignment removed.'));
    }
}
