<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    /**
     * Get all news articles
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = News::where('is_published', true)
            ->orderBy('published_at', 'desc');
            
        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Limit results if provided
        $limit = $request->get('limit', 10);
        $news = $query->paginate($limit);
        
        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'meta' => [
                'total' => $news->total(),
                'per_page' => $news->perPage(),
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage()
            ]
        ]);
    }

    /**
     * Get a single news article
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $news = News::find($id);
        
        if (!$news || !$news->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'News article not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Get all available news categories
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $categories = News::where('is_published', true)
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();
            
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get upcoming events
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upcomingEvents(Request $request)
    {
        $limit = $request->get('limit', 3);
        
        $events = Event::with('createdBy')
            ->where('start_date', '>=', Carbon::now())
            ->where('status', 'published')
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->start_date->toIso8601String(),
                    'endDate' => $event->end_date ? $event->end_date->toIso8601String() : null,
                    'location' => $event->location,
                    'organizer' => $event->createdBy ? $event->createdBy->name : 'School Administration',
                    'imageUrl' => $event->image ? asset('storage/' . $event->image) : null,
                    'isVirtual' => (bool) $event->is_virtual,
                    'meetingUrl' => $event->meeting_url,
                    'registrationDeadline' => $event->registration_deadline?->toIso8601String(),
                    'attendeeCount' => $event->attendees()->count(),
                    'maxAttendees' => $event->max_attendees,
                    'isRegistrationOpen' => $event->isRegistrationOpen(),
                    'isFull' => $event->isFull(),
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }
}
