<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Schema;
use App\Core\Support\Collection;
use App\Models\AdmissionSetting;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\News;
use App\Models\Notice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;

class HomeController extends Controller
{
    public function index(): void
    {
        $settings = Schema::hasTable('website_settings') ? WebsiteSetting::getSettings() : new WebsiteSetting();

        $homeContent = WebsiteContent::getContent('home', [
            'hero'         => [],
            'highlights'   => [],
            'testimonials' => [],
        ]);

        $latestNews = new Collection();
        $upcomingEvents = new Collection();
        $recentNotices = new Collection();
        $teachers = new Collection();
        $remarkableStudents = new Collection();
        $sliderFallback = new Collection();
        $committeeMembers = new Collection();

        try {
            if (Schema::hasTable('news')) {
                $latestNews = new Collection(News::query()
                    ->published()
                    ->where('is_event', false)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get());
            }
            if (Schema::hasTable('events')) {
                $upcomingEvents = new Collection(Event::query()
                    ->where('status', 'published')
                    ->where('start_date', '>=', now())
                    ->orderBy('start_date')
                    ->limit(5)
                    ->get());
            }
            if (Schema::hasTable('events')) {
                $sliderFallback = collect(Event::query()
                    ->where('status', 'published')
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get())
                    ->map(function (Event $e): array {
                        return [
                            'image'   => $e->image ? url('storage/' . ltrim((string) $e->image, '/')) : null,
                            'title'   => $e->title,
                            'caption' => (string) ($e->location ?? ''),
                            'link'    => route('site.events'),
                        ];
                    });
            }
            if (Schema::hasTable('notices')) {
                $recentNotices = new Collection(Notice::query()
                    ->orderByDesc('pinned')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get());
            }
            if (Schema::hasTable('teachers')) {
                $teachers = new Collection(Teacher::query()
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get());
            }
            if (Schema::hasTable('students') && Schema::hasColumn('students', 'is_notable')) {
                $remarkableStudents = new Collection(Student::query()
                    ->where('is_notable', true)
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get());
            }
            if (Schema::hasTable('committee_members')) {
                $committeeMembers = new Collection(CommitteeMember::query()
                    ->active()
                    ->ordered()
                    ->limit(20)
                    ->get());
            }
        } catch (\Throwable) {
            // A partially imported schema should never take down the homepage.
        }

        $stats = [
            'students' => 0,
            'teachers' => 0,
            'years'    => ($settings->established_year)
                ? max(0, (int) date('Y') - (int) $settings->established_year)
                : null,
            'awards'   => 0,
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
                $stats['students'] = Student::query()->count();
            }
            if (Schema::hasTable('users') && Schema::hasTable('teachers')) {
                $stats['teachers'] = Teacher::query()->count();
            }
        } catch (\Throwable) {
            //
        }

        $sliderSlides = $homeContent->content['slider'] ?? [];

        $this->view('home', compact(
            'settings',
            'homeContent',
            'latestNews',
            'upcomingEvents',
            'recentNotices',
            'teachers',
            'remarkableStudents',
            'sliderFallback',
            'sliderSlides',
            'stats',
            'admissionsOpen',
            'committeeMembers'
        ));
    }
}