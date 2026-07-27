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
                                  ->orWhere('body', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get()
                            ->map(fn ($item) => [
                                'title' => $item->title,
                                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($item->body), 150),
                                'url' => route('site.notices.show', $item->slug),
                                'type' => __('Notices'),
                                'date' => null,
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
                                'title' => $item->title,
                                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($item->description), 150),
                                'url' => route('dashboard.events.show', $item->id),
                                'type' => __('Events'),
                                'date' => $item->start_date?->format('M j, Y'),
                            ])
                    );
                }
            } catch (\Throwable) {
                //
            }
        }

        return view('site.search', compact('query', 'results'));
    }
}
