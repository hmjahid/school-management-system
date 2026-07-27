<?php

namespace App\Http\Controllers\Web;

use App\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;

class ManifestController
{
    public function __invoke(): JsonResponse
    {
        $settings = WebsiteSetting::getSettings();
        $name = $settings->school_name ?: config('app.name', 'SchoolEase');
        $shortName = \Illuminate\Support\Str::limit($name, 12, '');
        $themeColor = $settings->theme_primary_color ?? '#2563eb';
        $logoUrl = $settings->logo_url;

        $manifest = [
            'name' => $name,
            'short_name' => $shortName,
            'description' => $settings->meta_description ?? 'School management system',
            'start_url' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => $themeColor,
            'categories' => ['education'],
            'icons' => [
                [
                    'src' => $logoUrl ?: asset('favicon.ico'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $logoUrl ?: asset('favicon.ico'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
