<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::where('status', 'published')
            ->where('start_date', '>=', Carbon::now()->subDay())
            ->orderBy('start_date', 'asc');

        $limit = $request->input('limit', 20);
        $events = $query->limit($limit)->get()->map(fn ($event) => [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date' => $event->start_date->toIso8601String(),
            'endDate' => $event->end_date?->toIso8601String(),
            'location' => $event->location,
            'category' => $event->metadata['category'] ?? 'general',
            'imageUrl' => $event->image ? asset('storage/'.$event->image) : null,
            'isVirtual' => (bool) $event->is_virtual,
            'registrationRequired' => ! is_null($event->registration_deadline) || ! is_null($event->max_attendees),
            'registrationDeadline' => $event->registration_deadline?->toIso8601String(),
        ]);

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function show(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->start_date->toIso8601String(),
                'endDate' => $event->end_date?->toIso8601String(),
                'location' => $event->location,
                'category' => $event->metadata['category'] ?? 'general',
                'imageUrl' => $event->image ? asset('storage/'.$event->image) : null,
                'isVirtual' => (bool) $event->is_virtual,
                'meetingUrl' => $event->meeting_url,
                'registrationRequired' => ! is_null($event->registration_deadline) || ! is_null($event->max_attendees),
                'registrationDeadline' => $event->registration_deadline?->toIso8601String(),
                'maxAttendees' => $event->max_attendees,
                'attendeeCount' => $event->attendees()->count(),
                'isRegistrationOpen' => $event->isRegistrationOpen(),
                'isFull' => $event->isFull(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'is_virtual' => 'boolean',
            'meeting_url' => 'nullable|url|required_if:is_virtual,true',
            'registration_deadline' => 'nullable|date|before_or_equal:start_date',
            'max_attendees' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'published';
        $data['metadata'] = array_filter([
            'category' => $data['category'] ?? null,
        ]);

        unset($data['category']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event = Event::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'is_virtual' => 'boolean',
            'meeting_url' => 'nullable|url',
            'registration_deadline' => 'nullable|date',
            'max_attendees' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['success' => true, 'message' => 'Event deleted successfully']);
    }
}
