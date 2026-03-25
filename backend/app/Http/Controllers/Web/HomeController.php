<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use App\Models\Student;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $settings = Schema::hasTable('website_settings')
            ? WebsiteSetting::first()
            : null;

        $homeContent = WebsiteContent::getContent('home', [
            'hero' => [],
            'highlights' => [],
            'testimonials' => [],
        ]);

        $latestNews = collect();
        $upcomingEvents = collect();

        try {
            if (Schema::hasTable('news')) {
                $latestNews = News::query()
                    ->published()
                    ->where('is_event', false)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();
            }
            if (Schema::hasTable('events')) {
                $upcomingEvents = Event::query()
                    ->where('status', 'published')
                    ->where('start_date', '>=', now())
                    ->orderBy('start_date')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable) {
            //
        }

        $stats = [
            'students' => 0,
            'teachers' => 0,
            'years' => ($settings && $settings->established_year)
                ? max(0, (int) date('Y') - (int) $settings->established_year)
                : null,
        ];

        try {
            if (Schema::hasTable('students')) {
                $stats['students'] = Student::count();
            }
            if (Schema::hasTable('users')) {
                $stats['teachers'] = User::role('teacher')->count();
            }
        } catch (\Throwable) {
            //
        }

        return view('home', compact(
            'settings',
            'homeContent',
            'latestNews',
            'upcomingEvents',
            'stats'
        ));
    }
}
