<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Schema;
use App\Core\Session;
use App\Core\Support\Collection;
use App\Core\Support\LengthAwarePaginator;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Models\AdmissionSetting;
use App\Models\AdmissionTest;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Gallery;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Routine;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TransportRoute;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use App\Services\AdmissionSubmitter;

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

    public function students(): void
    {
        $this->studentsLife();
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

    public function admissions(): void
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
            $settings = AdmissionSetting::getSettings();
            $classes = new Collection(SchoolClass::query()->orderBy('name')->get());
        } catch (\Throwable) {
            //
        }

        $this->view('site.admissions', compact('content', 'admissionsClosed', 'settings', 'classes'));
    }

    public function apply(): void
    {
        $settings = AdmissionSetting::getSettings();

        if (!$settings->is_open) {
            $content = WebsiteContent::getContent('admissions');
            $this->view('site.admissions', compact('content') + ['admissionsClosed' => true]);
            return;
        }

        $content = WebsiteContent::getContent('admissions');

        $sessions = new Collection();
        $batches = new Collection();
        try {
            $sessions = new Collection(AcademicSession::query()
                ->where('is_active', true)
                ->orderByDesc('start_date')
                ->get());
            if ($sessions->isEmpty()) {
                $sessions = new Collection(AcademicSession::query()
                    ->orderByDesc('start_date')
                    ->limit(10)
                    ->get());
            }

            $batches = new Collection(Batch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get());
            if ($batches->isEmpty()) {
                $batches = new Collection(Batch::query()->orderBy('name')->limit(30)->get());
            }
        } catch (\Throwable) {
            //
        }

        $this->view('site.admissions-apply', compact('content', 'sessions', 'batches', 'settings'));
    }

    public function applyStore(): void
    {
        $settings = AdmissionSetting::getSettings();
        if (!$settings->is_open) {
            Session::getInstance()->flash('error', $settings->closed_message);
            $this->redirect('/admissions/apply');
        }

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
            'academic_session_id' => 'numeric',
            'batch_id'          => 'numeric',
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
            $admissionId = AdmissionSubmitter::submitPublicApplication($data, $_FILES ?? []);

            $admission = Admission::find((int) $admissionId);
            if ($admission) {
                $admission->admission_fee = $settings->admission_fee ?? 0;
                $admission->payment_number = $settings->payment_number;
                $admission->save();
            }

            Session::getInstance()->flash('success', __('Application submitted successfully. Save your application number for tracking.'));
            $this->redirect('/admissions/status?application_number=' . rawurlencode((string) ($admission->application_number ?? '')));
        } catch (\Throwable $e) {
            Session::getInstance()->flash('error', 'Could not submit application: ' . $e->getMessage());
            $this->back();
        }
    }

    public function admissionStatus(): void
    {
        $applicationNumber = trim((string) ($_GET['application_number'] ?? ''));
        $applicationNumber = $applicationNumber !== '' ? strtoupper($applicationNumber) : null;

        $admission = null;
        $settings = null;
        if ($applicationNumber) {
            try {
                $admission = Admission::query()
                    ->whereRaw('UPPER(application_number) = ?', [$applicationNumber])
                    ->first();
                $settings = AdmissionSetting::getSettings();
            } catch (\Throwable) {
                $admission = null;
                $settings = null;
            }
        }

        $this->view('site.admission-status', [
            'admission'         => $admission,
            'applicationNumber' => $applicationNumber,
            'settings'          => $settings,
        ]);
    }

    public function admissionReceipt(string $id): void
    {
        $admission = null;
        try {
            $admission = Admission::findOrFail((int) $id);
        } catch (\Throwable) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        $site = WebsiteSetting::getSettings();

        $this->view('site.admissions.admission-receipt', [
            'admission' => $admission,
            'site'      => $site,
        ]);
    }

    public function admissionApprovalLetter(string $id): void
    {
        $admission = null;
        try {
            $admission = Admission::find((int) $id);
        } catch (\Throwable) {
            $admission = null;
        }

        if (!$admission) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        if (!($admission->payment_status === Admission::PAYMENT_VERIFIED && $admission->status === Admission::STATUS_APPROVED)) {
            Session::getInstance()->flash('error', __('Approval letter is not yet available.'));
            $this->redirect('/admissions/status?application_number=' . rawurlencode((string) $admission->application_number));
        }

        $site = WebsiteSetting::getSettings();

        $this->view('site.admissions.admission-approval-letter', [
            'admission' => $admission,
            'site'      => $site,
        ]);
    }

    public function submitPayment(string $id): void
    {
        $admission = null;
        try {
            $admission = Admission::find((int) $id);
        } catch (\Throwable) {
            $admission = null;
        }

        if (!$admission) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        if ($admission->payment_status !== Admission::PAYMENT_UNPAID) {
            Session::getInstance()->flash('error', __('Payment details have already been submitted.'));
            $this->back();
            return;
        }

        $data = $this->validate([
            'transaction_id' => 'required|max:128',
            'payment_method' => 'required|in:' . implode(',', Admission::PAYMENT_METHODS),
        ]);

        $admission->transaction_id = $data['transaction_id'];
        $admission->payment_method = $data['payment_method'];
        $admission->payment_status = Admission::PAYMENT_SUBMITTED;
        $admission->paid_at = date('Y-m-d H:i:s');
        $admission->save();

        Session::getInstance()->flash('success', __('Payment details submitted. Verification pending.'));
        $this->redirect('/admissions/status?application_number=' . rawurlencode((string) $admission->application_number));
    }

    public function submitScholarship(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email',
            'phone'   => 'max:30',
            'message' => 'required|max:5000',
        ]);

        \App\Core\Database::getInstance()->insert('contact_submissions', [
            'type'       => 'scholarship',
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => __('Scholarship application'),
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', __('Scholarship request received. Our office will contact you.'));
        $this->redirect('/admissions');
    }

    public function submitComplaint(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email',
            'phone'   => 'max:30',
            'message' => 'required|max:5000',
        ]);

        \App\Core\Database::getInstance()->insert('contact_submissions', [
            'type'       => 'complaint',
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'subject'    => __('Complaint'),
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->success(null, __('Your complaint has been recorded. We will follow up shortly.'));
    }

    public function submitFeedback(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email',
            'message' => 'required|max:5000',
        ]);

        \App\Core\Database::getInstance()->insert('contact_submissions', [
            'type'       => 'feedback',
            'name'       => $data['name'],
            'email'      => $data['email'],
            'subject'    => __('Feedback'),
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->success(null, __('Thank you for your feedback.'));
    }

    public function newsletterStore(): void
    {
        $data = $this->validate([
            'email' => 'required|email|max:100',
        ]);

        \App\Core\Database::getInstance()->insert('contact_submissions', [
            'type'       => 'newsletter',
            'name'       => __('Newsletter subscriber'),
            'email'      => $data['email'],
            'subject'    => __('Newsletter subscription'),
            'message'    => __('Please add this email to the school newsletter list.'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->success(null, __('Thanks — you are subscribed to updates.'));
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
        $payment = null;
        try {
            $payment = Payment::find((int) $id);
        } catch (\Throwable) {
            $payment = null;
        }

        if (!$payment) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        $feePayment = null;
        $metadata = is_array($payment->metadata) ? $payment->metadata : [];
        if (!empty($metadata['fee_payment_id'])) {
            try {
                $feePayment = FeePayment::find((int) $metadata['fee_payment_id']);
            } catch (\Throwable) {
                $feePayment = null;
            }
        }

        $this->view('site.payment-status', ['payment' => $payment, 'feePayment' => $feePayment]);
    }

    public function feeReceipt(string $id): void
    {
        $feePayment = null;
        try {
            $feePayment = FeePayment::find((int) $id);
        } catch (\Throwable) {
            $feePayment = null;
        }

        if (!$feePayment) {
            http_response_code(404);
            $this->view('errors.404');
            return;
        }

        $this->view('site.fee-receipt', ['p' => $feePayment, 'feePayment' => $feePayment]);
    }

    public function resultsDownload(): void
    {
        $classId = (int) ($_GET['class_id'] ?? 0);
        $sessionId = (int) ($_GET['academic_session_id'] ?? 0);
        $roll = trim((string) ($_GET['roll'] ?? ''));

        if ($classId <= 0 || $sessionId <= 0 || $roll === '') {
            $this->redirect('/results');
        }

        $student = null;
        $result = new Collection();
        try {
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
        } catch (\Throwable) {
            $result = new Collection();
        }

        if (!$student) {
            Session::getInstance()->flash('error', __('Student not found.'));
            $this->redirect('/results');
        }

        $settings = WebsiteSetting::getSettings();

        $this->view('site.results-pdf', [
            'student'  => $student,
            'result'   => $result,
            'settings' => $settings,
        ]);
    }

    public function portal(): void
    {
        $session = Session::getInstance();

        // Staff session (user_id/user_role) with no portal session → dashboard.
        if (\App\Core\Auth::check() && !$session->has('student_id') && !$session->has('guardian_id')) {
            $this->redirect('/dashboard');
        }

        $user = $this->portalUser();

        $student = null;
        $children = new Collection();
        $assignments = new Collection();
        $recentAttendance = new Collection();
        $examResults = new Collection();
        $feePayments = new Collection();
        $announcements = new Collection();
        $upcomingEvents = new Collection();
        $routine = new Collection();
        $teachers = new Collection();
        $attendanceCalendar = new Collection();
        $duesTimeline = new Collection();

        try {
            if (Schema::hasTable('events')) {
                $upcomingEvents = new Collection(Event::query()
                    ->where('start_date', '>=', now()->format('Y-m-d'))
                    ->orderBy('start_date')
                    ->limit(10)
                    ->get());
            }
        } catch (\Throwable) {
            $upcomingEvents = new Collection();
        }

        if ($session->has('student_id')) {
            $student = null;
            try {
                $student = Student::find((int) $session->get('student_id'));
            } catch (\Throwable) {
                $student = null;
            }
            if ($student) {
                $this->loadStudentPortalData($student, $assignments, $recentAttendance, $examResults, $feePayments, $routine, $teachers);
                $attendanceCalendar = $this->attendanceCalendar([$student->id]);
            }
        } elseif ($session->has('guardian_id')) {
            $guardian = null;
            try {
                $guardian = Guardian::find((int) $session->get('guardian_id'));
            } catch (\Throwable) {
                $guardian = null;
            }
            if ($guardian) {
                try {
                    $children = $guardian->students;
                } catch (\Throwable) {
                    $children = new Collection();
                }

                $ids = $children->pluck('id');
                if ($ids->isNotEmpty()) {
                    try {
                        if (Schema::hasTable('attendances')) {
                            $recentAttendance = new Collection(Attendance::query()
                                ->whereIn('student_id', $ids->all())
                                ->orderByDesc('date')
                                ->limit(20)
                                ->get());
                        }
                        if (Schema::hasTable('exam_results')) {
                            $examResults = new Collection(ExamResult::query()
                                ->whereIn('student_id', $ids->all())
                                ->where('is_published', true)
                                ->orderByDesc('id')
                                ->limit(15)
                                ->get());
                        }
                        if (Schema::hasTable('fee_payments')) {
                            $feePayments = new Collection(FeePayment::query()
                                ->whereIn('student_id', $ids->all())
                                ->orderByDesc('payment_date')
                                ->limit(20)
                                ->get());
                        }
                        $attendanceCalendar = $this->attendanceCalendar($ids->all());
                        $classIds = $children->pluck('class_id')->filter()->unique();
                        $teachers = $this->teachersForClasses($classIds);
                    } catch (\Throwable) {
                        //
                    }
                }
            }
        }

        $duesTimeline = $feePayments->sortByDesc(fn ($fp) => $fp->payment_date ?? $fp->created_at)->values();

        $audience = $user->hasRole('parent') ? 'parent' : 'student';
        $announcements = new Collection();
        try {
            if (Schema::hasTable('announcements')) {
                $announcements = new Collection(Announcement::query()
                    ->published()
                    ->active()
                    ->orderByDesc('starts_at')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
                    ->filter(fn ($a) => in_array('all', (array) $a->audience, true)
                        || in_array($audience, (array) $a->audience, true)));
            }
        } catch (\Throwable) {
            $announcements = new Collection();
        }

        $this->view('site.portal', compact(
            'user',
            'student',
            'children',
            'assignments',
            'recentAttendance',
            'examResults',
            'feePayments',
            'announcements',
            'upcomingEvents',
            'routine',
            'teachers',
            'attendanceCalendar',
            'duesTimeline'
        ));
    }

    public function portalAdmission(): void
    {
        $user = $this->portalUser();
        $admission = null;
        try {
            if ($user->email !== '' && Schema::hasTable('admissions')) {
                $admission = Admission::query()
                    ->where('email', $user->email)
                    ->orderByDesc('id')
                    ->first();
            }
        } catch (\Throwable) {
            $admission = null;
        }

        $this->view('site.portal-admission', ['admission' => $admission]);
    }

    public function portalProgress(): void
    {
        $user = $this->portalUser();
        $session = Session::getInstance();

        $studentIds = new Collection();
        if ($user->hasRole('student')) {
            $id = (int) $session->get('student_id');
            $studentIds = $id > 0 ? new Collection([$id]) : new Collection();
        } elseif ($user->hasRole('parent')) {
            $guardian = null;
            try {
                $guardian = Guardian::find((int) $session->get('guardian_id'));
            } catch (\Throwable) {
                $guardian = null;
            }
            if ($guardian) {
                try {
                    $studentIds = new Collection($guardian->students->pluck('id')->map(fn ($v) => (int) $v)->all());
                } catch (\Throwable) {
                    $studentIds = new Collection();
                }
            }
        }

        $empty = [
            'studentIds'         => new Collection(),
            'student'            => null,
            'selectedStudentId'  => null,
            'selectedSessionId'  => null,
            'selectedExamType'   => null,
            'sessions'           => new Collection(),
            'examTypes'          => [],
            'examSummaries'      => new Collection(),
        ];

        if ($studentIds->isEmpty()) {
            $this->view('site.portal-progress', $empty);
            return;
        }

        $selectedStudentId = (int) ($_GET['student_id'] ?? $studentIds->first());
        if (!$studentIds->contains($selectedStudentId)) {
            $selectedStudentId = (int) $studentIds->first();
        }

        $selectedSessionId = (int) ($_GET['academic_session_id'] ?? 0);
        $selectedSessionId = $selectedSessionId > 0 ? $selectedSessionId : null;
        $selectedExamType = trim((string) ($_GET['exam_type'] ?? ''));
        $selectedExamType = $selectedExamType !== '' ? $selectedExamType : null;

        $sessions = new Collection();
        try {
            if (Schema::hasTable('academic_sessions')) {
                $sessions = new Collection(AcademicSession::query()->orderByDesc('start_date')->limit(10)->get());
            }
        } catch (\Throwable) {
            $sessions = new Collection();
        }

        $examTypes = [
            Exam::TYPE_QUIZ,
            Exam::TYPE_MID_TERM,
            Exam::TYPE_FINAL,
            Exam::TYPE_ASSIGNMENT,
            Exam::TYPE_PROJECT,
            Exam::TYPE_PRACTICAL,
            Exam::TYPE_ORAL,
            Exam::TYPE_OTHER,
        ];

        $examSummaries = new Collection();
        try {
            if (Schema::hasTable('exam_results') && Schema::hasTable('exams')) {
                $resultsQuery = ExamResult::query()
                    ->where('student_id', $selectedStudentId)
                    ->where('is_published', true)
                    ->orderByDesc('id');

                if ($selectedSessionId || $selectedExamType) {
                    $examQuery = Exam::query();
                    if ($selectedSessionId) {
                        $examQuery->where('academic_session_id', $selectedSessionId);
                    }
                    if ($selectedExamType) {
                        $examQuery->where('type', $selectedExamType);
                    }
                    $examIds = $examQuery->pluck('id');
                    if ($examIds === []) {
                        $results = new Collection();
                    } else {
                        $resultsQuery->whereIn('exam_id', $examIds);
                        $results = $resultsQuery->limit(200)->get();
                    }
                } else {
                    $results = $resultsQuery->limit(200)->get();
                }

                $byExam = $results->filter(fn ($r) => $r->exam)->groupBy(fn ($r) => $r->exam->id);
                $summaries = [];
                foreach ($byExam as $rows) {
                    $exam = $rows->first()->exam;
                    $gp = $rows->pluck('grade_point')->filter(fn ($v) => is_numeric($v))->all();
                    $marks = $rows->pluck('obtained_marks')->filter(fn ($v) => is_numeric($v))->all();
                    $summaries[] = [
                        'exam'            => $exam,
                        'count'           => $rows->count(),
                        'avg_grade_point' => $gp === [] ? null : array_sum($gp) / count($gp),
                        'avg_marks'       => $marks === [] ? null : array_sum($marks) / count($marks),
                        'rows'            => $rows,
                    ];
                }
                usort($summaries, static fn ($a, $b) => ($b['exam']?->start_date ?? $b['exam']?->id) <=> ($a['exam']?->start_date ?? $a['exam']?->id));
                $examSummaries = new Collection($summaries);
            }
        } catch (\Throwable) {
            $examSummaries = new Collection();
        }

        $student = null;
        try {
            $student = Student::find($selectedStudentId);
        } catch (\Throwable) {
            $student = null;
        }

        $this->view('site.portal-progress', [
            'studentIds'         => $studentIds,
            'student'            => $student,
            'selectedStudentId'  => $selectedStudentId,
            'selectedSessionId'  => $selectedSessionId,
            'selectedExamType'   => $selectedExamType,
            'sessions'           => $sessions,
            'examTypes'          => $examTypes,
            'examSummaries'      => $examSummaries,
        ]);
    }

    public function messageTeacher(): void
    {
        $data = $this->validate([
            'teacher_id' => 'required|numeric',
            'subject'    => 'required|max:120',
            'body'       => 'required|max:2000',
        ]);

        $teacher = null;
        try {
            $teacher = Teacher::find((int) $data['teacher_id']);
        } catch (\Throwable) {
            $teacher = null;
        }

        if (!$teacher) {
            Session::getInstance()->flash('error', __('Invalid teacher selected.'));
            $this->back();
            return;
        }

        $senderId = $this->currentUserId();
        if ($senderId > 0 && Schema::hasTable('messages')) {
            try {
                Message::create([
                    'sender_id'   => $senderId,
                    'receiver_id' => (int) $teacher->user_id,
                    'subject'     => $data['subject'],
                    'body'        => $data['body'],
                ]);
            } catch (\Throwable) {
                Session::getInstance()->flash('error', __('Could not send message.'));
                $this->back();
                return;
            }
        }

        Session::getInstance()->flash('success', site_ui('portal.message_sent'));
        $this->redirect('/portal');
    }

    public function portalRegister(): void
    {
        // No dedicated portal-register blade exists in this port yet; render the
        // portal home with empty data so the page renders instead of fataling.
        $this->view('site.portal', [
            'user'               => $this->portalUser(),
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

    /**
     * Public profile edit — resolves the user from the staff session or the
     * student/guardian portal session keys (never Auth::user() alone).
     */
    public function profileEdit(): void
    {
        $user = $this->profileUser();
        $this->view('profile.edit', ['user' => $user]);
    }

    public function profileUpdate(): void
    {
        $data = $this->validate([
            'name'           => 'required|max:255',
            'email'          => 'required|email',
            'phone'          => 'max:50',
            'gender'         => 'max:20',
            'date_of_birth'  => 'date',
            'address'        => 'max:500',
        ]);

        $userId = $this->currentUserId();
        if ($userId <= 0) {
            Session::getInstance()->flash('error', __('Please login to update your profile.'));
            $this->redirect('/login');
            return;
        }

        try {
            $exists = \App\Core\Database::getInstance()->fetch(
                "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL LIMIT 1",
                [$data['email'], $userId]
            );
        } catch (\Throwable) {
            $exists = null;
        }
        if ($exists) {
            Session::getInstance()->flash('error', __('This email is already in use.'));
            $this->back();
            return;
        }

        try {
            \App\Core\Database::getInstance()->update('users', [
                'name'           => $data['name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'gender'         => $data['gender'] ?? null,
                'date_of_birth'  => $data['date_of_birth'] ?? null,
                'address'        => $data['address'] ?? null,
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$userId]);
        } catch (\Throwable) {
            Session::getInstance()->flash('error', __('Could not update profile.'));
            $this->back();
            return;
        }

        Session::getInstance()->flash('success', __('Profile updated successfully.'));
        $this->redirect('/profile');
    }

    public function switchLocale(string $locale): void
    {
        $locales = config('school.supported_locales', ['en', 'bn']);
        if (in_array($locale, $locales, true)) {
            Session::getInstance()->set('locale', $locale);
        }
        $this->back();
    }

    public function dashboardSwitchLocale(string $locale): void
    {
        $locales = config('school.supported_locales', ['en', 'bn']);
        if (!in_array($locale, $locales, true)) {
            http_response_code(400);
            echo 'Bad Request';
            exit;
        }
        Session::getInstance()->set('dashboard_locale', $locale);
        Session::getInstance()->flash('success', __('Language changed.'));
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }

    /**
     * Build the current portal/staff user facade from session keys.
     */
    private function portalUser(): User
    {
        $session = Session::getInstance();
        $role = 'guest';
        $userId = 0;
        $name = '';
        $email = '';
        $studentId = null;

        if ($session->has('student_id')) {
            $role = 'student';
            $userId = (int) ($session->get('student_user_id') ?: 0);
            $student = null;
            try {
                $student = Student::find((int) $session->get('student_id'));
            } catch (\Throwable) {
                $student = null;
            }
            $studentId = $student?->id;
            $name = $student?->name
                ?? trim((string) ($student?->first_name ?? '') . ' ' . (string) ($student?->last_name ?? ''));
            $email = (string) ($student?->email ?? '');
        } elseif ($session->has('guardian_id')) {
            $role = 'parent';
            $userId = (int) ($session->get('guardian_user_id') ?: 0);
            $guardian = null;
            try {
                $guardian = Guardian::find((int) $session->get('guardian_id'));
            } catch (\Throwable) {
                $guardian = null;
            }
            $name = (string) ($guardian?->name ?? '');
            $email = (string) ($guardian?->email ?? '');
        } elseif (\App\Core\Auth::check()) {
            $role = \App\Core\Auth::role() ?? 'user';
            $userId = \App\Core\Auth::id() ?? 0;
            $linked = \App\Core\Auth::user();
            $name = $linked?->name ?? '';
            $email = $linked?->email ?? '';
        }

        if ($userId > 0) {
            try {
                $linkedUser = User::find($userId);
                if ($linkedUser) {
                    $name = $linkedUser->name ?? $name;
                    $email = $linkedUser->email ?? $email;
                }
            } catch (\Throwable) {
                //
            }
        }

        $user = new User();
        $user->id = $userId;
        $user->name = $name;
        $user->email = $email;
        $user->role = $role;
        $user->student_id = $studentId;
        return $user;
    }

    /**
     * Full User model (name/email/phone/date_of_birth/photo…) for profile pages.
     */
    private function profileUser(): User
    {
        $session = Session::getInstance();
        $linkedId = 0;
        if ($session->has('student_id')) {
            $linkedId = (int) ($session->get('student_user_id') ?: 0);
        } elseif ($session->has('guardian_id')) {
            $linkedId = (int) ($session->get('guardian_user_id') ?: 0);
        }
        if ($linkedId > 0) {
            try {
                $linked = User::find($linkedId);
                if ($linked) {
                    return $linked;
                }
            } catch (\Throwable) {
                //
            }
        }
        return $this->portalUser();
    }

    private function currentUserId(): int
    {
        $session = Session::getInstance();
        if ($session->has('student_id')) {
            return (int) ($session->get('student_user_id') ?: 0);
        }
        if ($session->has('guardian_id')) {
            return (int) ($session->get('guardian_user_id') ?: 0);
        }
        return \App\Core\Auth::id() ?? 0;
    }

    private function loadStudentPortalData(
        Student $student,
        Collection &$assignments,
        Collection &$recentAttendance,
        Collection &$examResults,
        Collection &$feePayments,
        Collection &$routine,
        Collection &$teachers
    ): void {
        try {
            if ($student->batch_id && Schema::hasTable('assignments')) {
                $assignments = new Collection(Assignment::query()
                    ->where('batch_id', $student->batch_id)
                    ->orderByDesc('due_date')
                    ->limit(15)
                    ->get());
            }
        } catch (\Throwable) {
            $assignments = new Collection();
        }

        try {
            if (Schema::hasTable('attendances')) {
                $recentAttendance = new Collection(Attendance::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('date')
                    ->limit(14)
                    ->get());
            }
        } catch (\Throwable) {
            $recentAttendance = new Collection();
        }

        try {
            if (Schema::hasTable('exam_results')) {
                $examResults = new Collection(ExamResult::query()
                    ->where('student_id', $student->id)
                    ->where('is_published', true)
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get());
            }
        } catch (\Throwable) {
            $examResults = new Collection();
        }

        try {
            if (Schema::hasTable('fee_payments')) {
                $feePayments = new Collection(FeePayment::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('payment_date')
                    ->limit(15)
                    ->get());
            }
        } catch (\Throwable) {
            $feePayments = new Collection();
        }

        try {
            if (Schema::hasTable('routines')) {
                $routine = new Collection(Routine::query()
                    ->where('school_class_id', $student->class_id)
                    ->where('is_active', true)
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get()
                    ->groupBy('day_of_week'));
            }
        } catch (\Throwable) {
            $routine = new Collection();
        }

        try {
            if ($student->class_id && Schema::hasTable('class_teacher') && Schema::hasTable('teachers')) {
                $teachers = $this->teachersForClasses(collect([$student->class_id]));
            }
        } catch (\Throwable) {
            $teachers = new Collection();
        }
    }

    /**
     * @param  array<int,int>  $studentIds
     */
    private function attendanceCalendar(array $studentIds): Collection
    {
        if ($studentIds === [] || !Schema::hasTable('attendances')) {
            return new Collection();
        }
        try {
            $rows = Attendance::query()
                ->whereIn('student_id', $studentIds)
                ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->orderByDesc('date')
                ->get();
            return new Collection($rows->groupBy(fn ($a) => $a->date?->format('Y-m-d') ?? 'unknown'));
        } catch (\Throwable) {
            return new Collection();
        }
    }

    private function teachersForClasses(Collection $classIds): Collection
    {
        if ($classIds->isEmpty() || !Schema::hasTable('class_teacher') || !Schema::hasTable('teachers')) {
            return new Collection();
        }
        try {
            $ids = array_values(array_filter($classIds->all(), static fn ($v) => $v !== null && $v !== ''));
            if ($ids === []) {
                return new Collection();
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = \App\Core\Database::getInstance()->fetchAll(
                "SELECT DISTINCT teacher_id FROM class_teacher WHERE class_id IN ({$placeholders})",
                $ids
            );
            $teacherIds = array_values(array_unique(array_map(static fn ($r) => (int) $r['teacher_id'], $rows)));
            if ($teacherIds === []) {
                return new Collection();
            }
            return new Collection(Teacher::query()->whereIn('id', $teacherIds)->get());
        } catch (\Throwable) {
            return new Collection();
        }
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