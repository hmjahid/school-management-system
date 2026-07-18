<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use App\Models\WebsiteContent;
use Illuminate\View\View;

class SiteNewsController extends Controller
{
    public function index(): View
    {
        $content = WebsiteContent::getContent('news');

        $latestNews = News::query()
            ->published()
            ->where('is_event', false)
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        $newsEvents = News::query()
            ->published()
            ->events()
            ->orderByDesc('event_date')
            ->limit(8)
            ->get();

        $upcomingEvents = Event::query()
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        $pastEvents = Event::query()
            ->where('status', 'published')
            ->where('start_date', '<', now())
            ->orderByDesc('start_date')
            ->limit(8)
            ->get();

        return view('site.news', compact(
            'content',
            'latestNews',
            'newsEvents',
            'upcomingEvents',
            'pastEvents'
        ));
    }

    public function show(string $slug): View
    {
        $article = News::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('site.news-show', compact('article'));
    }
}
