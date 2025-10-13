<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Get all gallery items
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = \App\Models\Gallery::query();
        
        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        $items = $query->orderBy('created_at', 'desc')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'category' => $item->category,
                'image' => $item->image_path,
                'date' => $item->created_at->toDateString(),
                'featured' => $item->is_featured ?? false
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
    
    /**
     * Get all available gallery categories
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $categories = \App\Models\Gallery::select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->map(function($category) {
                return [
                    'id' => $category,
                    'name' => ucfirst($category)
                ];
            })
            ->values();
            
        // Add 'All' category at the beginning
        $categories->prepend([
            'id' => 'all',
            'name' => 'All'
        ]);
            
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
    
    /**
     * Get all unique categories
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getCategories()
    {
        return \App\Models\Gallery::select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();
    }
}
