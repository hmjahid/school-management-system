<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WebsiteSettingController extends Controller
{
    /**
     * Display the website settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $settings = WebsiteSetting::first();
        
        if (!$settings) {
            // If no settings exist, return default settings
            $settings = new WebsiteSetting();
        }
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update the website settings in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $settings = WebsiteSetting::firstOrNew();
        
        // Validate the request data
        $validatedData = $this->validateRequest($request, $settings->id);
        
        try {
            // Handle file uploads
            if ($request->hasFile('logo')) {
                $validatedData['logo_path'] = $this->uploadFile($request->file('logo'), 'logos', $settings->logo_path);
            }
            
            if ($request->hasFile('favicon')) {
                $validatedData['favicon_path'] = $this->uploadFile($request->file('favicon'), 'favicons', $settings->favicon_path);
            }
            
            // Update or create the settings
            $settings->fill($validatedData);
            $settings->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Website settings updated successfully',
                'data' => $settings
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update website settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate the request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $id
     * @return array
     */
    private function validateRequest($request, $id = null)
    {
        $rules = [
            'school_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'established_year' => 'required|integer|min:1800|max:' . (date('Y') + 1),
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'website' => 'nullable|url|max:255',
            'opening_hours' => 'nullable|json',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'timezone' => 'required|timezone',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:50',
            'maintenance_mode' => 'boolean',
            'maintenance_message' => 'nullable|string|max:1000',
        ];
        
        return $request->validate($rules);
    }
    
    /**
     * Upload a file and return the path.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $folder
     * @param  string|null  $oldFilePath
     * @return string
     */
    private function uploadFile($file, $folder, $oldFilePath = null)
    {
        // Delete old file if exists
        if ($oldFilePath && Storage::exists($oldFilePath)) {
            Storage::delete($oldFilePath);
        }
        
        // Generate a unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
        
        // Store the file in the specified folder (e.g., 'public/logos' or 'public/favicons')
        $path = $file->storeAs('public/' . $folder, $filename);
        
        // Return the path relative to the storage directory
        return str_replace('public/', '', $path);
    }
    
    /**
     * Get the website settings for public access.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function publicSettings()
    {
        $settings = WebsiteSetting::first();
        
        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => new WebsiteSetting()
            ]);
        }
        
        // Return only the necessary fields for public access
        return response()->json([
            'success' => true,
            'data' => [
                'school_name' => $settings->school_name,
                'tagline' => $settings->tagline,
                'logo_url' => $settings->logo_url,
                'favicon_url' => $settings->favicon_url,
                'established_year' => $settings->established_year,
                'contact' => [
                    'address' => $settings->address,
                    'city' => $settings->city,
                    'state' => $settings->state,
                    'country' => $settings->country,
                    'postal_code' => $settings->postal_code,
                    'phone' => $settings->phone,
                    'email' => $settings->email,
                    'website' => $settings->website,
                ],
                'social_links' => [
                    'facebook' => $settings->facebook_url,
                    'twitter' => $settings->twitter_url,
                    'instagram' => $settings->instagram_url,
                    'linkedin' => $settings->linkedin_url,
                    'youtube' => $settings->youtube_url,
                ],
                'opening_hours' => $settings->opening_hours,
            ]
        ]);
    }
}
