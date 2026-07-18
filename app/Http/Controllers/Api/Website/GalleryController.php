<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Get all gallery items with optional filtering
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'featured']);
        
        // This is sample data - in a real app, this would come from a database
        $galleryItems = $this->getSampleGalleryItems();
        
        // Apply filters if provided
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $galleryItems = array_filter($galleryItems, function($item) use ($filters) {
                return $item['category'] === $filters['category'];
            });
        }
        
        if (isset($filters['featured'])) {
            $isFeatured = filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN);
            $galleryItems = array_filter($galleryItems, function($item) use ($isFeatured) {
                return $item['featured'] === $isFeatured;
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => array_values($galleryItems), // Reindex array after filtering
            'message' => 'Gallery items retrieved successfully.'
        ]);
    }
    
    /**
     * Get all available gallery categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = [
            ['id' => 'all', 'name' => 'All'],
            ['id' => 'events', 'name' => 'Events'],
            ['id' => 'sports', 'name' => 'Sports'],
            ['id' => 'academics', 'name' => 'Academics'],
            ['id' => 'cultural', 'name' => 'Cultural'],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Gallery categories retrieved successfully.'
        ]);
    }
    
    /**
     * Get sample gallery items
     * In a real application, this would come from a database
     */
    private function getSampleGalleryItems()
    {
        return [
            [
                'id' => 1,
                'title' => 'Annual Day Celebration',
                'description' => 'Our annual day celebration with cultural performances',
                'image' => asset('storage/gallery/event1.jpg'),
                'category' => 'events',
                'date' => '2023-10-10',
                'featured' => true,
                'created_at' => '2023-10-10 10:00:00',
                'updated_at' => '2023-10-10 10:00:00',
            ],
            [
                'id' => 2,
                'title' => 'Sports Day',
                'description' => 'Annual sports day competition',
                'image' => asset('storage/gallery/sports1.jpg'),
                'category' => 'sports',
                'date' => '2023-11-15',
                'featured' => true,
                'created_at' => '2023-11-15 09:30:00',
                'updated_at' => '2023-11-15 09:30:00',
            ],
            [
                'id' => 3,
                'title' => 'Science Fair',
                'description' => 'Students showcasing their science projects',
                'image' => asset('storage/gallery/academics1.jpg'),
                'category' => 'academics',
                'date' => '2023-09-20',
                'featured' => true,
                'created_at' => '2023-09-20 10:00:00',
                'updated_at' => '2023-09-20 10:00:00',
            ],
            [
                'id' => 4,
                'title' => 'Cultural Festival',
                'description' => 'Traditional dance performances',
                'image' => asset('storage/gallery/cultural1.jpg'),
                'category' => 'cultural',
                'date' => '2023-12-05',
                'featured' => false,
                'created_at' => '2023-12-05 14:00:00',
                'updated_at' => '2023-12-05 14:00:00',
            ],
            // Add more sample items as needed
        ];
    }
}
