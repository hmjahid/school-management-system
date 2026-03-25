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

        if (! $content) {
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
            'title' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'content_en' => 'nullable|array',
            'content_bn' => 'nullable|array',
            'cms_input_mode' => 'nullable|string|in:json,form',
            'meta_description' => 'nullable|string|max:500',
            'meta_description_en' => 'nullable|string|max:500',
            'meta_description_bn' => 'nullable|string|max:500',
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

        $contentEn = $request->input('content_en', $request->input('content', []));
        if (! is_array($contentEn)) {
            $contentEn = [];
        }
        $contentBn = $request->input('content_bn', []);
        if (! is_array($contentBn)) {
            $contentBn = [];
        }

        $titleEn = $request->input('title_en') ?? $request->input('title', '');
        if ($titleEn === '') {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['title_en' => ['Title (title or title_en) is required.']],
            ], 422);
        }
        $titleBn = $request->input('title_bn') ?: $titleEn;

        $metaEn = $request->input('meta_description_en') ?? $request->input('meta_description');
        $metaBn = $request->input('meta_description_bn') ?: $metaEn;

        $content = WebsiteContent::updateOrCreate(
            ['page' => $page],
            [
                'title' => $titleEn,
                'title_en' => $titleEn,
                'title_bn' => $titleBn,
                'content' => $contentEn,
                'content_en' => $contentEn,
                'content_bn' => $contentBn,
                'cms_input_mode' => $request->input('cms_input_mode', WebsiteContent::INPUT_MODE_JSON),
                'meta_description' => $metaEn,
                'meta_description_en' => $metaEn,
                'meta_description_bn' => $metaBn,
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
            'url' => asset('storage/'.$path),
            'path' => $path,
        ]);
    }
}
