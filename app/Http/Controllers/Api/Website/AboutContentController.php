<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AboutContentController extends Controller
{
    /**
     * Get about page content
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $content = AboutContent::getContent();
        return response()->json($content);
    }

    /**
     * Get contact information
     *
     * @return JsonResponse
     */
    public function contact(): JsonResponse
    {
        $content = AboutContent::getContent();
        return response()->json([
            'address' => $content->address,
            'phone' => $content->phone,
            'email' => $content->email,
            'website' => $content->website,
            'social_links' => $content->social_links,
            'contact_info' => $content->contact_info
        ]);
    }

    /**
     * Update about content (admin only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => 'sometimes|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'established_year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'about_summary' => 'sometimes|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'history' => 'nullable|string',
            'core_values' => 'nullable|array',
            'contact_info' => 'nullable|array',
            'social_links' => 'nullable|array',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|max:255',
            'website' => 'nullable|url|max:255',
        ]);

        $about = AboutContent::firstOrNew();
        $about->fill($validated);
        $about->save();

        return response()->json([
            'message' => 'About content updated successfully',
            'data' => $about
        ]);
    }

    /**
     * Upload logo (admin only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        return $this->handleFileUpload($request->file('logo'), 'logo');
    }

    /**
     * Upload favicon (admin only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        $request->validate([
            'favicon' => 'required|image|mimes:png,ico,x-icon|max:1024',
        ]);

        return $this->handleFileUpload($request->file('favicon'), 'favicon');
    }

    /**
     * Handle file uploads
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $type
     * @return JsonResponse
     */
    private function handleFileUpload($file, string $type): JsonResponse
    {
        $about = AboutContent::firstOrNew();
        $oldFile = $about->{$type . '_path'};
        
        // Delete old file if exists
        if ($oldFile && Storage::exists($oldFile)) {
            Storage::delete($oldFile);
        }

        // Store new file
        $path = $file->store('public/' . $type . 's');
        $about->{$type . '_path'} = $path;
        $about->save();

        return response()->json([
            'message' => ucfirst($type) . ' uploaded successfully',
            'url' => Storage::url($path)
        ]);
    }
}
