<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdmissionSetting;
use App\Models\Event;
use App\Models\News;
use App\Models\Notice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $recentEvents = collect();
        $recentNotices = collect();
        $teachers = collect();
        $remarkableStudents = collect();
        $sliderFallback = collect();

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
            if (Schema::hasTable('events')) {
                $recentEvents = Event::query()
                    ->where('status', 'published')
                    ->where('start_date', '<', now())
                    ->orderByDesc('start_date')
                    ->limit(5)
                    ->get();
            }
            if (Schema::hasTable('events')) {
                $sliderFallback = Event::query()
                    ->where('status', 'published')
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get()
                    ->map(function (Event $e): array {
                        return [
                            'image' => $e->image ? Storage::url($e->image) : null,
                            'title' => $e->title,
                            'caption' => $e->location ?? '',
                            'link' => route('site.events'),
                        ];
                    });
            }
            if (Schema::hasTable('notices')) {
                $recentNotices = Notice::query()
                    ->orderByDesc('pinned')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get();
            }
            if (Schema::hasTable('teachers')) {
                $teachers = Teacher::query()
                    ->where('status', 'active')
                    ->with('user')
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get();
            }
            if (Schema::hasTable('students') && Schema::hasColumn('students', 'is_notable')) {
                $remarkableStudents = Student::query()
                    ->where('is_notable', true)
                    ->with('user')
                    ->orderByDesc('id')
                    ->limit(8)
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

        $admissionsOpen = true;
        if (Schema::hasTable('admission_settings')) {
            try {
                $admissionsOpen = (bool) AdmissionSetting::getSettings()->is_open;
            } catch (\Throwable) {
                //
            }
        }

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

        $sliderSlides = $homeContent->content['slider'] ?? [];

        return view('home', compact(
            'settings',
            'homeContent',
            'latestNews',
            'upcomingEvents',
            'recentEvents',
            'recentNotices',
            'teachers',
            'remarkableStudents',
            'sliderFallback',
            'sliderSlides',
            'stats',
            'admissionsOpen'
        ));
    }
}
