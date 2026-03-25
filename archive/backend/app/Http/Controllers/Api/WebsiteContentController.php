<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebsiteContentController extends Controller
{
    /**
     * Get content for a specific page
     */
    public function getPageContent(string $page)
    {
        $content = WebsiteContent::where('page', $page)
            ->where('is_active', true)
            ->first();

        if (!$content) {
            // Return default content if page not found
            $content = new WebsiteContent([
                'page' => $page,
                'title' => Str::title(str_replace('-', ' ', $page)),
                'content' => [],
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    /**
     * Get all active pages
     */
    public function getActivePages()
    {
        $pages = WebsiteContent::where('is_active', true)
            ->select('page', 'title', 'meta_description', 'created_at', 'updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Update or create page content (Admin)
     */
    public function updatePageContent(Request $request, string $page)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|array',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $content = WebsiteContent::updateOrCreate(
            ['page' => $page],
            [
                'title' => $request->title,
                'content' => $request->content,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
                'settings' => $request->settings ?? [],
                'is_active' => $request->has('is_active') ? $request->is_active : true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Content updated successfully',
            'data' => $content,
        ]);
    }

    /**
     * Upload images for a page (Admin)
     */
    public function uploadImage(Request $request, string $page)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'field_name' => 'required|string',
        ]);

        $content = WebsiteContent::where('page', $page)->firstOrFail();
        $path = $request->file('image')->store('website/images', 'public');
        
        // Update the images array
        $images = $content->images ?? [];
        $images[$request->field_name] = $path;
        $content->images = $images;
        $content->save();

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }
}
