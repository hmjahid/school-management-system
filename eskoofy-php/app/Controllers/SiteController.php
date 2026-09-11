<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Schema;
use App\Core\Support\Collection;
use App\Core\Support\LengthAwarePaginator;
use App\Models\AdmissionSetting;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\Gallery;
use App\Models\News;
use App\Models\Notice;
use App\Models\PaymentGateway;
use App\Models\Routine;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\WebsiteContent;
use App\Models\AcademicSession;
use App\Models\ExamResult;

class SiteController extends Controller
{
    protected function renderCmsPage(string $slug): void
    {
        $content = WebsiteContent::getContent($slug);
        $this->view('site.page', ['slug' => $slug, 'content' => $content]);
    }

    public function about(): void
    {
        $this->renderCmsPage('about');
    }

    public function academics(): void
    {
        $this->renderCmsPage('academics');
    }

    public function studentsLife(): void
    {
        $this->renderCmsPage('students');
    }

    public function terms(): void
    {
        $this->renderCmsPage('terms');
    }

    public function privacy(): void
    {
        $this->renderCmsPage('privacy');
    }

    public function careers(): void
    {
        $this->renderCmsPage('careers');
    }

    public function news(): void
    {
        $content = WebsiteContent::getContent('news');

        $latestNews = new Collection();
        $newsEvents = new Collection();
        $upcomingEvents = new Collection();
        $pastEvents = new Collection();

        try {
            $latestNews = new Collection(News::query()
                ->published()
                ->where('is_event', false)
                ->orderByDesc('published_at')
                ->limit(12)
                ->get());

            $newsEvents = new Collection(News::query()
                ->published()
                ->events()
                ->orderByDesc('event_date')
                ->limit(8)
                ->get());

            $upcomingEvents = new Collection(Event::query()
                ->where('status', 'published')
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->limit(8)
                ->get());

            $pastEvents = new Collection(Event::query()
                ->where('status', 'published')
                ->where('start_date', '<', now())
                ->orderByDesc('start_date')
                ->limit(8)
                ->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.news', compact('content', 'latestNews', 'newsEvents', 'upcomingEvents', 'pastEvents'));
    }

    public function newsShow(string $slug): void
    {
        $article = News::query()->published()->where('slug', $slug)->first();
        if (!$article) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }
        $this->view('site.news-show', ['article' => $article]);
    }

    public function notices(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $notices = new LengthAwarePaginator([], 0, 15, $page);
        try {
            $paginated = Notice::query()
                ->orderByDesc('pinned')
                ->orderByDesc('id')
                ->paginate(15, $page);
            $notices = LengthAwarePaginator::fromQuery($paginated);
        } catch (\Throwable) {
            //
        }

        $this->view('site.notices', ['notices' => $notices]);
    }

    public function events(): void
    {
        $upcoming = new Collection();
        $past = new Collection();
        try {
            $upcoming = new Collection(Event::query()
                ->where('start_date', '>=', now())
                ->where('status', 'published')
                ->orderBy('start_date')
                ->limit(50)
                ->get());

            $past = new Collection(Event::query()
                ->where('start_date', '<', now())
                ->where('status', 'published')
                ->orderByDesc('start_date')
                ->limit(20)
                ->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.events', ['upcoming' => $upcoming, 'past' => $past]);
    }

    public function gallery(): void
    {
        $content = WebsiteContent::getContent('gallery');

        $items = new Collection();
        try {
            $items = collect(Gallery::query()
                ->published()
                ->orderByDesc('id')
                ->get())
                ->groupBy(fn ($g) => $g->category ?: __('General'));
        } catch (\Throwable) {
            //
        }

        $this->view('site.gallery', ['content' => $content, 'items' => $items]);
    }

    public function contact(): void
    {
        $content = WebsiteContent::getContent('contact');
        $this->view('site.contact', ['content' => $content]);
    }

    public function faculty(): void
    {
        $content = WebsiteContent::getContent('faculty');

        $teachers = new Collection();
        try {
            $teachers = new Collection(Teacher::query()
                ->where('status', 'active')
                ->orderByDesc('joining_date')
                ->limit(80)
                ->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.faculty', ['content' => $content, 'teachers' => $teachers]);
    }

    public function committee(): void
    {
        $content = WebsiteContent::getContent('committee');

        $members = new Collection();
        try {
            $members = new Collection(CommitteeMember::query()->active()->ordered()->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.committee', ['content' => $content, 'members' => $members]);
    }

    public function transport(): void
    {
        $routes = new Collection();
        try {
            $routes = new Collection(TransportRoute::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get());
        } catch (\Throwable) {
            //
        }
        $routeNames = $routes->pluck('name')->filter()->values();

        $this->view('site.transport', ['routes' => $routes, 'routeNames' => $routeNames]);
    }

    public function routine(): void
    {
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sectionId = (int) ($_GET['section_id'] ?? 0);

        $classes = new Collection();
        $sections = new Collection();
        $routines = new Collection();
        try {
            $classes = new Collection(SchoolClass::query()->orderBy('name')->get());
            $sections = new Collection(\App\Models\Section::query()->orderBy('name')->get());
            $query = Routine::query();
            if ($classId > 0) {
                $query->where('school_class_id', $classId);
            }
            if ($sectionId > 0) {
                $query->where('section_id', $sectionId);
            }
            $routines = new Collection($query->orderBy('day_of_week')->orderBy('start_time')->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.routines', compact('classes', 'sections', 'routines', 'classId', 'sectionId'));
    }

    public function results(): void
    {
        $classes = new Collection();
        $sessions = new Collection();
        $result = new Collection();
        $student = null;

        try {
            $classes = new Collection(SchoolClass::query()->orderBy('name')->get(['id', 'name']));
            $sessions = new Collection(AcademicSession::query()->orderByDesc('name')->get(['id', 'name']));

            $classId = (int) ($_GET['class_id'] ?? 0);
            $sessionId = (int) ($_GET['academic_session_id'] ?? 0);
            $roll = trim((string) ($_GET['roll'] ?? ''));

            if ($classId > 0 && $sessionId > 0 && $roll !== '') {
                $student = Student::query()
                    ->where('class_id', $classId)
                    ->whereRaw('(roll_no = ? OR roll_number = ?)', [$roll, $roll])
                    ->first();

                if ($student) {
                    $examIds = Exam::query()
                        ->where('is_published_to_public', true)
                        ->where('academic_session_id', $sessionId)
                        ->where('batch_id', $student->batch_id)
                        ->pluck('id');

                    if ($examIds !== []) {
                        $result = new Collection(ExamResult::query()
                            ->whereIn('exam_id', $examIds)
                            ->where('student_id', $student->id)
                            ->where('is_published', true)
                            ->get());
                    }
                }
            }
        } catch (\Throwable) {
            //
        }

        $this->view('site.results', compact('classes', 'sessions', 'result', 'student'));
    }

    public function admission(): void
    {
        $content = WebsiteContent::getContent('admissions');
        $admissionsClosed = true;
        try {
            $admissionsClosed = !AdmissionSetting::getSettings()->is_open;
        } catch (\Throwable) {
            //
        }

        $settings = [];
        $classes = new Collection();
        try {
            $settings = \App\Models\AdmissionSetting::getSettings();
            $classes = new Collection(SchoolClass::query()->orderBy('name')->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.admissions', compact('content', 'admissionsClosed', 'settings', 'classes'));
    }

    public function payments(): void
    {
        $content = WebsiteContent::getContent('payments');

        $feeRows = new Collection();
        $gateways = new Collection();
        $feePayments = new Collection();
        $students = new Collection();
        try {
            $feeRows = new Collection(Fee::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(40)
                ->get());

            $gateways = new Collection(PaymentGateway::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.payments', compact('content', 'feeRows', 'gateways', 'feePayments', 'students'));
    }

    public function paymentStatus(string $id): void
    {
        $this->view('site.payment-status', ['payment' => null]);
    }

    public function feeReceipt(string $id): void
    {
        $this->view('site.fee-receipt', ['feePayment' => null]);
    }

    public function portal(): void
    {
        if (!\App\Core\Auth::check()) {
            $this->redirect('/login');
        }
        $this->view('site.portal', [
            'student'            => null,
            'children'           => new Collection(),
            'assignments'        => new Collection(),
            'recentAttendance'   => new Collection(),
            'examResults'        => new Collection(),
            'feePayments'        => new Collection(),
            'announcements'      => new Collection(),
            'upcomingEvents'     => new Collection(),
            'routine'            => new Collection(),
            'teachers'           => new Collection(),
            'attendanceCalendar' => new Collection(),
            'duesTimeline'       => new Collection(),
        ]);
    }

    public function search(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $activeType = $_GET['type'] ?? 'all';
        $results = new Collection();

        if (mb_strlen($query) >= 2) {
            try {
                if (Schema::hasTable('news')) {
                    $results = $results->merge(collect(News::query()->published()
                        ->whereRaw('(title LIKE ? OR content LIKE ?)', ["%{$query}%", "%{$query}%"])
                        ->limit(10)
                        ->get())
                        ->map(fn ($item) => [
                            'type_key' => 'news',
                            'title'    => $item->title,
                            'excerpt'  => \App\Core\Support\Str::limit(strip_tags((string) $item->content), 150),
                            'url'      => route('site.news.show', $item->slug),
                            'type'     => __('News'),
                            'date'     => $item->published_at?->format('M j, Y'),
                        ]));
                }
                if (Schema::hasTable('notices')) {
                    $results = $results->merge(collect(Notice::query()
                        ->whereRaw('(title LIKE ? OR content LIKE ?)', ["%{$query}%", "%{$query}%"])
                        ->limit(10)
                        ->get())
                        ->map(fn ($item) => [
                            'type_key' => 'notice',
                            'title'    => $item->localizedTitle(),
                            'excerpt'  => \App\Core\Support\Str::limit(strip_tags($item->localizedContent()), 150),
                            'url'      => route('site.notices'),
                            'type'     => __('Notices'),
                            'date'     => $item->created_at?->format('M j, Y'),
                        ]));
                }
                if (Schema::hasTable('events')) {
                    $results = $results->merge(collect(Event::query()
                        ->where('status', 'published')
                        ->whereRaw('(title LIKE ? OR description LIKE ?)', ["%{$query}%", "%{$query}%"])
                        ->limit(10)
                        ->get())
                        ->map(fn ($item) => [
                            'type_key' => 'event',
                            'title'    => $item->title,
                            'excerpt'  => \App\Core\Support\Str::limit(strip_tags((string) $item->description), 150),
                            'url'      => route('site.events'),
                            'type'     => __('Events'),
                            'date'     => $item->start_date?->format('M j, Y'),
                        ]));
                }
            } catch (\Throwable) {
                //
            }
        }

        $this->view('site.search', ['query' => $query, 'results' => $results, 'activeType' => $activeType]);
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml');
        $this->view('site.sitemap-xml');
    }

    public function submitContact(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email',
            'phone'   => 'max:30',
            'subject' => 'max:200',
            'message' => 'required|max:5000',
        ]);

        \App\Core\Database::getInstance()->insert('contact_submissions', [
            'type'       => 'contact',
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => $data['subject'] ?? null,
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        \App\Core\Session::getInstance()->flash('success', 'Thank you. We will get back to you soon.');
        $this->redirect('/contact');
    }

    public function submitAdmission(): void
    {
        $data = $this->validate([
            'first_name'        => 'required|max:100',
            'last_name'         => 'required|max:100',
            'gender'            => 'required|in:male,female,other',
            'date_of_birth'     => 'required|date',
            'email'             => 'required|email',
            'phone'             => 'required|max:30',
            'address'           => 'required|max:500',
            'city'              => 'max:100',
            'state'             => 'max:100',
            'country'           => 'max:100',
            'postal_code'       => 'max:20',
            'blood_group'       => 'max:10',
            'religion'          => 'max:50',
            'nationality'       => 'max:100',
            'class_id'          => 'required|numeric',
            'father_name'       => 'required|max:191',
            'father_phone'      => 'required|max:30',
            'father_occupation' => 'max:191',
            'mother_name'       => 'required|max:191',
            'mother_phone'      => 'max:30',
            'mother_occupation' => 'max:191',
            'guardian_name'     => 'max:191',
            'guardian_relation' => 'max:50',
            'guardian_phone'    => 'max:30',
            'previous_school'   => 'max:255',
            'previous_class'    => 'max:100',
            'previous_grade'    => 'max:50',
            'notes'             => 'max:2000',
        ]);

        try {
            \App\Services\AdmissionSubmitter::submitPublicApplication($data, $_FILES ?? []);
            \App\Core\Session::getInstance()->flash('success', 'Admission application submitted successfully.');
        } catch (\Throwable $e) {
            \App\Core\Session::getInstance()->flash('error', 'Could not submit application: ' . $e->getMessage());
            $this->back();
        }
        $this->redirect('/admission');
    }

    public function applyCareer(): void
    {
        $data = $this->validate([
            'name'  => 'required|max:191',
            'email' => 'required|email',
            'phone' => 'required|max:30',
        ]);

        \App\Core\Database::getInstance()->insert('job_applications', [
            'career_id'   => (int) ($_POST['career_id'] ?? 0),
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        \App\Core\Session::getInstance()->flash('success', 'Application submitted successfully.');
        $this->back();
    }
}