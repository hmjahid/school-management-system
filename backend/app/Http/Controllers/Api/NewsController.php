<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
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
        
        $events = News::where('is_published', true)
            ->where('is_event', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->limit($limit)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }
}
