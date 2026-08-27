<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|super-admin');
    }

    // Pages
    public function pages(): JsonResponse
    {
        $pages = WebsiteContent::select('page', 'title', 'title_en', 'title_bn', 'meta_description', 'is_active', 'updated_at')
            ->get();

        return response()->json(['success' => true, 'data' => $pages]);
    }

    public function showPage(string $page): JsonResponse
    {
        $content = WebsiteContent::where('page', $page)->firstOrFail();

        return response()->json(['success' => true, 'data' => $content]);
    }

    public function storePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string|max:255|unique:website_contents,page',
            'title' => 'required|string|max:255',
            'content' => 'nullable|array',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $content = WebsiteContent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $content,
        ], 201);
    }

    public function updatePage(Request $request, string $page): JsonResponse
    {
        $content = WebsiteContent::where('page', $page)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|array',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $content->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $content,
        ]);
    }

    public function destroyPage(string $page): JsonResponse
    {
        $content = WebsiteContent::where('page', $page)->firstOrFail();
        $content->delete();

        return response()->json(['success' => true, 'message' => 'Page deleted successfully']);
    }

    // Media
    public function media(): JsonResponse
    {
        $files = [];
        $storagePath = storage_path('app/public/cms');
        if (is_dir($storagePath)) {
            $files = collect(Storage::disk('public')->files('cms'))
                ->map(fn ($path) => [
                    'id' => md5($path),
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => url('storage/'.ltrim($path, '/')),
                    'mime_type' => Storage::mimeType($path),
                    'size' => Storage::size($path),
                    'created_at' => date('c', Storage::lastModified($path)),
                ])->values();
        }

        return response()->json(['success' => true, 'data' => $files]);
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('cms', 'public');

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => Storage::url($path),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ], 201);
    }

    public function destroyMedia(string $id): JsonResponse
    {
        $files = Storage::disk('public')->files('cms');
        $target = collect($files)->first(fn ($path) => md5($path) === $id);

        if (! $target) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        Storage::disk('public')->delete($target);

        return response()->json(['success' => true, 'message' => 'Media deleted successfully']);
    }

    // Menus - stored in website_settings as JSON
    public function menus(): JsonResponse
    {
        $settings = WebsiteSetting::first();
        $menus = $settings ? ($settings->menus ?? []) : [];

        return response()->json(['success' => true, 'data' => $menus]);
    }

    public function updateMenus(Request $request): JsonResponse
    {
        $request->validate([
            'menus' => 'required|array',
            'menus.*.label' => 'required|string|max:255',
            'menus.*.url' => 'required|string|max:255',
        ]);

        $settings = WebsiteSetting::firstOrNew();
        $settings->menus = $request->menus;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Menus updated successfully',
        ]);
    }

    // Settings
    public function settings(): JsonResponse
    {
        $settings = WebsiteSetting::first();

        return response()->json(['success' => true, 'data' => $settings ?? []]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => 'nullable|string|max:255',
            'school_name_bn' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'tagline_bn' => 'nullable|string|max:500',
            'established_year' => 'nullable|integer|min:1800|max:2100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opening_hours' => 'nullable|array',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|max:100',
            'date_format' => 'nullable|string|max:50',
            'time_format' => 'nullable|string|max:50',
            'default_locale' => 'nullable|in:en,bn',
            'section_visibility' => 'nullable|array',
            'maintenance_mode' => 'boolean',
            'maintenance_message' => 'nullable|string|max:1000',
            'send_absence_sms' => 'boolean',
            'absence_sms_template' => 'nullable|string|max:1000',
            'sms_sender_id' => 'nullable|string|max:50',
            'theme_primary_color' => 'nullable|string|max:50',
            'theme_secondary_color' => 'nullable|string|max:50',
            'theme_font_family' => 'nullable|string|max:100',
            'theme_border_radius' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
            'default_payment_method' => 'nullable|in:bkash,nagad,rocket,cash,bank',
            'theme_header_style' => 'nullable|string|max:50',
            'theme_footer_style' => 'nullable|string|max:50',
            'theme_button_style' => 'nullable|string|max:50',
            'theme_section_spacing' => 'nullable|string|max:50',
            'theme_style' => 'nullable|string|max:50',
            'academic_start_month' => 'nullable|integer|min:1|max:12',
            'student_id_prefix' => 'nullable|string|max:20',
            'website_url' => 'nullable|url|max:255',
            'footer_description' => 'nullable|string|max:1000',
            'show_facebook' => 'boolean',
            'show_twitter' => 'boolean',
            'show_instagram' => 'boolean',
            'show_linkedin' => 'boolean',
            'show_youtube' => 'boolean',
        ]);

        $settings = WebsiteSetting::firstOrNew();
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings,
        ]);
    }

    // Header
    public function header(): JsonResponse
    {
        $header = WebsiteSetting::select('school_name', 'tagline', 'logo_path')->first();

        return response()->json(['success' => true, 'data' => $header ?? []]);
    }

    public function updateHeader(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => 'nullable|string|max:255',
            'school_name_bn' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'tagline_bn' => 'nullable|string|max:500',
            'logo_path' => 'nullable|string|max:500',
        ]);

        $settings = WebsiteSetting::firstOrNew();
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Header updated successfully',
        ]);
    }

    // Footer
    public function footer(): JsonResponse
    {
        $footer = WebsiteSetting::select('school_name', 'address', 'phone', 'email', 'facebook_url', 'twitter_url', 'instagram_url')->first();

        return response()->json(['success' => true, 'data' => $footer ?? []]);
    }

    public function updateFooter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => 'nullable|string|max:255',
            'school_name_bn' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
        ]);

        $settings = WebsiteSetting::firstOrNew();
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Footer updated successfully',
        ]);
    }

    // Content Blocks - stored in website_settings as JSON
    public function contentBlocks(): JsonResponse
    {
        $settings = WebsiteSetting::first();
        $blocks = $settings ? ($settings->content_blocks ?? []) : [];

        return response()->json(['success' => true, 'data' => $blocks]);
    }

    public function showContentBlock(int $id): JsonResponse
    {
        $settings = WebsiteSetting::first();
        $blocks = $settings ? ($settings->content_blocks ?? []) : [];
        $block = collect($blocks)->firstWhere('id', $id);

        if (! $block) {
            return response()->json(['success' => false, 'message' => 'Block not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $block]);
    }

    public function storeContentBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'type' => 'required|string|max:50',
        ]);

        $settings = WebsiteSetting::firstOrNew();
        $blocks = $settings->content_blocks ?? [];
        $validated['id'] = count($blocks) > 0 ? max(array_column($blocks, 'id')) + 1 : 1;
        $blocks[] = $validated;
        $settings->content_blocks = $blocks;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Content block created',
            'data' => $validated,
        ], 201);
    }

    public function updateContentBlock(Request $request, int $id): JsonResponse
    {
        $settings = WebsiteSetting::firstOrNew();
        $blocks = collect($settings->content_blocks ?? []);
        $index = $blocks->search(fn ($b) => ($b['id'] ?? null) == $id);

        if ($index === false) {
            return response()->json(['success' => false, 'message' => 'Block not found'], 404);
        }

        $updated = array_merge($blocks[$index], $request->only(['name', 'title', 'content', 'type']));
        $blocks[$index] = $updated;
        $settings->content_blocks = $blocks->values()->toArray();
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Content block updated',
            'data' => $updated,
        ]);
    }

    public function destroyContentBlock(int $id): JsonResponse
    {
        $settings = WebsiteSetting::firstOrNew();
        $blocks = collect($settings->content_blocks ?? []);
        $filtered = $blocks->reject(fn ($b) => ($b['id'] ?? null) == $id)->values();
        $settings->content_blocks = $filtered->toArray();
        $settings->save();

        return response()->json(['success' => true, 'message' => 'Content block deleted']);
    }
}
