<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $base = rtrim(config('app.url') ?: url('/'), '/');

        $static = [
            '/',
            '/about',
            '/academics',
            '/admissions',
            '/admissions/apply',
            '/admissions/status',
            '/students',
            '/faculty',
            '/news',
            '/gallery',
            '/contact',
            '/terms',
            '/privacy',
            '/payments',
            '/portal',
        ];

        $urls = collect($static)->map(fn ($path) => [
            'loc' => $base.$path,
            'lastmod' => now()->toDateString(),
        ]);

        try {
            $news = News::query()
                ->where('is_published', true)
                ->orderByDesc('published_at')
                ->limit(500)
                ->get(['slug', 'updated_at']);

            foreach ($news as $n) {
                $urls->push([
                    'loc' => $base.'/news/'.$n->slug,
                    'lastmod' => ($n->updated_at ?? now())->toDateString(),
                ]);
            }
        } catch (\Throwable) {
            // If DB isn't ready, still serve static URLs.
        }

        $xml = view('site.sitemap-xml', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
