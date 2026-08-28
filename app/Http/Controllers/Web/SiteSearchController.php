<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Notice;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SiteSearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->input('q', '');
        $activeType = $request->input('type', 'all');
        $results = collect();

        if (strlen($query) >= 2) {
            try {
                if (Schema::hasTable('news')) {
                    $results = $results->merge(
                        News::query()->published()
                            ->where(function ($q) use ($query) {
                                $q->where('title', 'like', "%{$query}%")
                                  ->orWhere('content', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get()
                            ->map(fn ($item) => [
                                'type_key' => 'news',
                                'title' => $item->title,
                                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($item->content), 150),
                                'url' => route('site.news.show', $item->slug),
                                'type' => __('News'),
                                'date' => $item->published_at?->format('M j, Y'),
                            ])
                    );
                }

                if (Schema::hasTable('notices')) {
                    $results = $results->merge(
                        Notice::query()
                            ->where(function ($q) use ($query) {
                                $q->where('title', 'like', "%{$query}%")
                                  ->orWhere('content', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get()
                            ->map(fn ($item) => [
                                'type_key' => 'notice',
                                'title' => $item->localizedTitle(),
                                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($item->localizedContent()), 150),
                                'url' => route('site.notices'),
                                'type' => __('Notices'),
                                'date' => $item->created_at?->format('M j, Y'),
                            ])
                    );
                }

                if (Schema::hasTable('events')) {
                    $results = $results->merge(
                        Event::query()
                            ->where('status', 'published')
                            ->where(function ($q) use ($query) {
                                $q->where('title', 'like', "%{$query}%")
                                  ->orWhere('description', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get()
                            ->map(fn ($item) => [
                                'type_key' => 'event',
                                'title' => $item->title,
                                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($item->description), 150),
                                'url' => route('site.events'),
                                'type' => __('Events'),
                                'date' => $item->start_date?->format('M j, Y'),
                            ])
                    );
                }

                if (Schema::hasTable('website_contents')) {
                    $pages = [
                        'about' => ['title' => __('About Us'), 'url' => route('site.about')],
                        'academics' => ['title' => __('Academics'), 'url' => route('site.academics')],
                        'admissions' => ['title' => __('Admissions'), 'url' => route('site.admissions')],
                        'faculty' => ['title' => __('Faculty'), 'url' => route('site.faculty')],
                        'committee' => ['title' => __('Managing Committee'), 'url' => route('site.committee')],
                        'contact' => ['title' => __('Contact Us'), 'url' => route('site.contact')],
                    ];
                    foreach ($pages as $slug => $meta) {
                        $content = \App\Models\WebsiteContent::where('page', $slug)->first();
                        if ($content) {
                            $text = $content->title . ' ' . strip_tags(json_encode($content->content));
                            if (stripos($text, $query) !== false) {
                                $results->push([
                                    'type_key' => 'page',
                                    'title' => $content->title ?? $meta['title'],
                                    'excerpt' => \Illuminate\Support\Str::limit(strip_tags(json_encode($content->content)), 150),
                                    'url' => $meta['url'],
                                    'type' => __('Page'),
                                    'date' => null,
                                ]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Site search error: ' . $e->getMessage());
            }
        }

        if ($activeType !== 'all') {
            $results = $results->where('type_key', $activeType)->values();
        }

        $grouped = $results->groupBy('type_key');
        $typeCounts = $results->countBy('type_key');

        return view('site.search', compact('query', 'results', 'grouped', 'typeCounts', 'activeType'));
    }
}
