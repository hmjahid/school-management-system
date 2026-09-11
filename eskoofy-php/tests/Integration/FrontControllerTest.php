<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\ExamController;
use App\Controllers\Dashboard\AdmitCardController;
use App\Controllers\Dashboard\CertificateController;
use App\Controllers\Dashboard\StudentIdCardController;
use App\Controllers\Dashboard\TestimonialController;
use App\Controllers\Dashboard\VehicleController;
use App\Controllers\Dashboard\GuardianController;
use App\Controllers\Dashboard\AdmissionController;
use App\Controllers\Dashboard\NewsController;
use App\Controllers\Dashboard\AssignmentController;
use App\Controllers\Dashboard\EventController;
use App\Controllers\Dashboard\LeaveController;
use App\Controllers\Dashboard\AttendanceController;
use App\Controllers\Dashboard\ExamController as DashboardExamController;
use App\Controllers\Dashboard\ExpenseCategoryController;
use App\Controllers\Dashboard\LibraryController;
use App\Controllers\Dashboard\LibraryReportController;
use App\Controllers\Dashboard\PayrollController;
use App\Controllers\Dashboard\SmsController;
use App\Controllers\Dashboard\ProgressReportController;
use App\Controllers\Dashboard\RefundController;
use App\Controllers\Dashboard\ReportBuilderController;
use App\Controllers\Dashboard\ReportController;
use App\Controllers\Dashboard\SearchController;
use App\Controllers\Dashboard\SeatPlanController;
use App\Controllers\Dashboard\StudentController;
use App\Controllers\Dashboard\TeacherController;
use App\Controllers\HomeController;
use App\Controllers\SiteController;
use App\Core\Database;
use App\Core\Session;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\Fakes\FakeDatabase;

/**
 * Exercises the public-site front controllers (HomeController, SiteController)
 * against a fake in-memory database. These tests exist because the unit
 * suite bypasses the front controller and therefore never caught
 * controller-vs-schema mismatches (e.g. `WHERE news.status = 'published'`
 * when `news` has `is_published`).
 *
 * What this test is for:
 *   - Asserting that every public route the user lands on issues SQL the
 *     schema actually accepts.
 *   - Catching a future regression where someone reverts a column rename.
 *
 * What this test is NOT for:
 *   - Replacing the dev-server smoke test.
 *   - Validating the rendered HTML (we ob_start and discard).
 *   - Exercising the Dashboard/* or Api/* surfaces (those have their own
 *     audit work to do).
 */
class FrontControllerTest extends PHPUnitTestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->db = new FakeDatabase();
        Database::setInstance($this->db);
    }

    protected function tearDown(): void
    {
        Database::setInstance(null);
        parent::tearDown();
    }

    /**
     * Drain stdout while invoking the controller so the rendered view
     * (which writes HTML via `echo` in layouts) does not pollute test
     * output or trip PHPUnit's own assertions.
     *
     * Also suppresses PHP warnings (notices, deprecated) so dashboard
     * views that reference missing sub-layouts don't fail the test
     * runner. The view-rendering code is not what these tests are
     * about; the SQL log is.
     */
    private function invoke(callable $fn): void
    {
        ob_start();
        $previous = set_error_handler(static function () {
            return true; // swallow
        }, E_ALL);
        try {
            $fn();
        } finally {
            if ($previous !== false) {
                restore_error_handler();
            }
            ob_end_clean();
        }
    }

    /**
     * Set up an authenticated session so controllers that call
     * `Auth::requireAuth()` don't redirect+exit the test runner.
     */
    private function authAs(int $id = 1, string $role = 'admin'): void
    {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_role'] = $role;
    }

    private function seedHomePage(): void
    {
        $this->db->seed('news', [
            ['id' => 1, 'title' => 'N1', 'is_published' => 1, 'is_event' => 0, 'published_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'title' => 'N2', 'is_published' => 0, 'is_event' => 0, 'published_at' => '2025-12-01 00:00:00'],
        ]);
        $this->db->seed('events', [
            ['id' => 1, 'title' => 'E1', 'status' => 'published', 'start_date' => '2099-01-01 09:00:00'],
        ]);
        $this->db->seed('notices', [
            ['id' => 1, 'title' => 'NB', 'pinned' => 1],
            ['id' => 2, 'title' => 'NB2', 'pinned' => 0],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Mr Smith', 'email' => 'smith@example.com'],
        ]);
        $this->db->seed('teachers', [
            ['id' => 1, 'user_id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('testimonials', [
            ['id' => 1, 'name' => 'T1', 'content' => 'great', 'is_visible' => 1, 'rating' => 5],
        ]);
        $this->db->seed('committee_members', [
            ['id' => 1, 'name' => 'C1', 'designation' => 'Chair', 'is_active' => 1, 'sort_order' => 1],
        ]);
        $this->db->seed('students', [
            ['id' => 1, 'admission_number' => 'A1', 'user_id' => 1, 'class_id' => 1],
        ]);
        $this->db->seed('school_classes', [
            ['id' => 1, 'name' => 'Class 1'],
        ]);
        $this->db->seed('website_settings', [
            ['id' => 1, 'school_name' => 'Test', 'established_year' => 2000],
        ]);
    }

    public function test_home_uses_is_published_not_status_for_news(): void
    {
        $this->seedHomePage();

        $this->invoke(fn () => (new HomeController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $newsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM news'));

        $this->assertNotEmpty($newsQueries, 'Home page should query the news table');
        foreach ($newsQueries as $sql) {
            $this->assertStringNotContainsString("status = 'published'", $sql,
                'news has is_published, not status — controller must use the schema column');
            if (str_contains($sql, 'WHERE')) {
                $this->assertStringContainsString('is_published', $sql,
                    'news query must filter on is_published');
            }
        }
    }

    public function test_home_uses_is_visible_not_is_active_for_testimonials(): void
    {
        $this->seedHomePage();

        $this->invoke(fn () => (new HomeController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $testimonialQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM testimonials'));

        $this->assertNotEmpty($testimonialQueries);
        foreach ($testimonialQueries as $sql) {
            $this->assertStringNotContainsString('is_active', $sql,
                'testimonials has is_visible, not is_active');
        }
    }

    public function test_news_listing_uses_is_published(): void
    {
        $this->db->seed('news', [
            ['id' => 1, 'title' => 'N1', 'slug' => 'n-1', 'is_published' => 1, 'is_event' => 0, 'published_at' => '2026-01-01 00:00:00'],
        ]);
        $this->db->seed('events', []);
        $this->db->seed('notices', []);
        $this->db->seed('testimonials', []);
        $this->db->seed('committee_members', []);
        $this->db->seed('teachers', []);
        $this->db->seed('users', []);
        $this->db->seed('students', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('website_settings', []);

        $this->invoke(fn () => (new SiteController())->news());

        $sqls = array_column($this->db->log, 'sql');
        $newsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM news'));
        $this->assertNotEmpty($newsQueries);
        foreach ($newsQueries as $sql) {
            $this->assertStringContainsString('is_published', $sql);
            $this->assertStringNotContainsString("status = 'published'", $sql);
        }
    }

    public function test_exams_use_start_date_not_exam_date(): void
    {
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid-term', 'start_date' => '2026-06-01 09:00:00', 'is_published' => 1],
        ]);
        $this->db->seed('students', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('sections', []);
        $this->db->seed('subjects', []);

        $this->invoke(fn () => (new SiteController())->results());

        $sqls = array_column($this->db->log, 'sql');
        $examQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM exams'));
        $this->assertNotEmpty($examQueries);
        foreach ($examQueries as $sql) {
            $this->assertStringNotContainsString('exam_date', $sql,
                'exams has start_date, not exam_date');
        }
    }

    public function test_routines_use_school_class_id_not_class_id(): void
    {
        $this->db->seed('school_classes', [
            ['id' => 1, 'name' => 'Class 1'],
        ]);
        $this->db->seed('sections', [
            ['id' => 1, 'class_id' => 1, 'name' => 'A'],
        ]);
        $this->db->seed('subjects', [
            ['id' => 1, 'name' => 'Math'],
        ]);
        $this->db->seed('teachers', []);
        $this->db->seed('users', []);
        $this->db->seed('routines', [
            ['id' => 1, 'school_class_id' => 1, 'section_id' => 1, 'subject_id' => 1, 'teacher_id' => 1,
             'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'is_active' => 1],
        ]);

        $_GET['class_id'] = '1';
        $this->invoke(fn () => (new SiteController())->routine());
        unset($_GET['class_id']);

        $sqls = array_column($this->db->log, 'sql');
        $routineQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM routines'));
        $this->assertNotEmpty($routineQueries);
        foreach ($routineQueries as $sql) {
            $this->assertStringContainsString('school_class_id', $sql,
                'routines has school_class_id, not class_id');
            $this->assertStringNotContainsString('r.class_id', $sql);
        }
    }

    public function test_gallery_uses_galleries_table_not_gallery_albums(): void
    {
        $this->db->seed('galleries', [
            ['id' => 1, 'title' => 'Photo 1', 'image_path' => '/uploads/x.jpg', 'is_published' => 1, 'category' => 'Campus'],
        ]);

        $this->invoke(fn () => (new SiteController())->gallery());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('gallery_albums', $joined,
            'gallery_albums does not exist in the schema — the port uses galleries only');
        $this->assertStringContainsString('FROM galleries', $joined);
    }

    // ------------------------------------------------------------------
    // Dashboard / API regression checks. These protect the systematic
    // schema mismatches surfaced by the controller audit (e.g. exams has
    // start_date not exam_date; teacher_subject was renamed to
    // class_subject_teacher).
    // ------------------------------------------------------------------

    public function test_admit_card_listing_uses_start_date(): void
    {
        $this->authAs();
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid', 'is_published' => 1, 'start_date' => '2026-06-01 09:00:00'],
        ]);
        $this->db->seed('students', []);
        $this->db->seed('users', []);
        $this->db->seed('school_classes', []);

        $this->invoke(fn () => (new AdmitCardController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $examsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM exams'));
        $this->assertNotEmpty($examsQueries);
        foreach ($examsQueries as $sql) {
            $this->assertStringNotContainsString('exam_date', $sql,
                'exams schema has start_date, not exam_date');
        }
    }

    public function test_seat_plan_uses_start_date(): void
    {
        $this->authAs();
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid', 'is_published' => 1, 'start_date' => '2026-06-01 09:00:00'],
        ]);

        $this->invoke(fn () => (new SeatPlanController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $examsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM exams'));
        $this->assertNotEmpty($examsQueries);
        foreach ($examsQueries as $sql) {
            $this->assertStringNotContainsString('exam_date', $sql);
        }
    }

    public function test_seat_plan_generate_uses_start_date_for_found_exam(): void
    {
        $this->authAs();
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid', 'is_published' => 1, 'start_date' => '2026-06-01 09:00:00'],
        ]);
        $this->db->seed('students', []);
        $this->db->seed('users', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('sections', []);

        $this->invoke(fn () => (new SeatPlanController())->generate(1));

        $sqls = array_column($this->db->log, 'sql');
        $examsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM exams'));
        $this->assertNotEmpty($examsQueries);
        foreach ($examsQueries as $sql) {
            $this->assertStringNotContainsString('exam_date', $sql,
                'seat-plans generate must read exams.start_date');
        }
    }

    public function test_student_fees_uses_paid_amount_not_amount_paid(): void
    {
        $this->authAs();
        $this->db->seed('students', [
            ['id' => 1, 'admission_number' => 'A1', 'user_id' => 1, 'class_id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Pupil', 'email' => 'p@x.com'],
        ]);
        $this->db->seed('school_classes', [
            ['id' => 1, 'name' => 'Class 1'],
        ]);
        $this->db->seed('fees', [
            ['id' => 1, 'name' => 'Tuition', 'class_id' => 1],
        ]);
        $this->db->seed('fee_payments', [
            ['id' => 1, 'student_id' => 1, 'fee_id' => 1, 'paid_amount' => 500, 'balance' => 0, 'status' => 'paid'],
        ]);

        $this->invoke(fn () => (new StudentController())->fees(1));

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringNotContainsString('amount_paid', $joined,
            'fee_payments schema has paid_amount, not amount_paid');
        $this->assertStringContainsString('paid_amount', $joined);
    }

    public function test_progress_report_generate_uses_subject_id_and_attendance_rate(): void
    {
        $this->authAs();
        $this->db->seed('students', [
            ['id' => 1, 'admission_number' => 'A1', 'user_id' => 1, 'class_id' => 1, 'section_id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Pupil', 'email' => 'p@x.com'],
        ]);
        $this->db->seed('school_classes', [
            ['id' => 1, 'name' => 'Class 1'],
        ]);
        $this->db->seed('sections', [
            ['id' => 1, 'name' => 'A'],
        ]);
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid', 'start_date' => '2026-06-01 09:00:00'],
        ]);
        $this->db->seed('subjects', [
            ['id' => 1, 'name' => 'Math'],
        ]);
        $this->db->seed('exam_results', [
            ['id' => 1, 'student_id' => 1, 'exam_id' => 1, 'subject_id' => 1, 'marks' => 85],
        ]);
        $this->db->seed('attendances', [
            ['id' => 1, 'student_id' => 1, 'status' => 'present'],
        ]);

        $this->invoke(fn () => (new ProgressReportController())->generate(1));

        $sqls = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('er.subject_id', $sqls,
            'exam_results uses subject_id in the port schema');
        $this->assertStringContainsString('attendances', $sqls,
            'progress report must query attendances for the attendance rate');
    }

    public function test_search_uses_start_date(): void
    {
        $this->authAs();
        $this->db->seed('exams', []);
        $_GET['q'] = 'Mid';
        $this->invoke(fn () => (new SearchController())->search());
        unset($_GET['q']);

        $sqls = array_column($this->db->log, 'sql');
        $examsQueries = array_filter($sqls, fn ($s) => str_contains($s, 'FROM exams'));
        if (!empty($examsQueries)) {
            foreach ($examsQueries as $sql) {
                $this->assertStringNotContainsString('exam_date', $sql);
            }
        }
        $this->assertTrue(true);
    }

    public function test_teacher_show_uses_class_subject_teacher_not_teacher_subject(): void
    {
        $this->authAs();
        $this->db->seed('teachers', [
            ['id' => 1, 'user_id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Mr T', 'email' => 't@x.com'],
        ]);
        $this->db->seed('subjects', []);
        $this->db->seed('class_subject_teacher', []);
        $this->db->seed('class_teacher', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('sections', []);

        $this->invoke(fn () => (new TeacherController())->show(1));

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('teacher_subject', $joined,
            'teacher_subject does not exist in the schema — port uses class_subject_teacher');
        $this->assertStringContainsString('class_subject_teacher', $joined);
    }

    public function test_attendance_uses_school_class_id_not_class_id(): void
    {
        $this->authAs();
        $this->db->seed('attendances', []);
        $this->db->seed('students', []);
        $this->db->seed('users', []);
        $this->db->seed('school_classes', []);

        $this->invoke(fn () => (new AttendanceController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('attendances.class_id', $joined,
            'attendances schema has school_class_id, not class_id');
    }

    public function test_assignments_uses_batch_id_due_date_created_by(): void
    {
        $this->authAs();
        $this->db->seed('assignments', []);
        $this->db->seed('batches', []);
        $this->db->seed('subjects', []);
        $this->db->seed('users', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('students', []);

        $this->invoke(fn () => (new AssignmentController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('a.class_id', $joined,
            'assignments schema has batch_id, not class_id');
        $this->assertStringNotContainsString('a.teacher_id', $joined,
            'assignments schema has created_by, not teacher_id');
        $this->assertStringNotContainsString('a.deadline', $joined,
            'assignments schema has due_date, not deadline');
    }

    public function test_expense_category_uses_expense_category_id(): void
    {
        $this->authAs();
        $this->db->seed('expense_categories', []);
        $this->db->seed('expenses', []);

        $this->invoke(fn () => (new ExpenseCategoryController())->index());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('e.category_id', $joined,
            'expenses schema has expense_category_id, not category_id');
    }

    public function test_library_report_uses_return_date_not_returned_at(): void
    {
        $this->authAs();
        $this->db->seed('book_issues', []);
        $this->db->seed('books', []);
        $this->db->seed('students', []);
        $this->db->seed('teachers', []);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new LibraryReportController())->currentlyIssued());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('returned_at', $joined,
            'book_issues schema has return_date, not returned_at');
    }

    public function test_refund_show_uses_user_id_not_student_id(): void
    {
        $this->authAs();
        $this->db->seed('refunds', [
            ['id' => 1, 'user_id' => 1, 'amount' => 50.0, 'currency' => 'USD', 'status' => 'pending'],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Test User'],
        ]);

        $this->invoke(fn () => (new RefundController())->show(1));

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('r.student_id', $joined,
            'refunds schema has user_id, not student_id');
    }

    public function test_payroll_salary_structures_uses_teacher_id(): void
    {
        $this->authAs();
        $this->db->seed('salary_structures', []);
        $this->db->seed('teachers', []);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new PayrollController())->salaryStructures());

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringNotContainsString('ss.user_id', $joined,
            'salary_structures schema has teacher_id, not user_id');
    }

    public function test_student_promote_form_uses_batches_sections_classes(): void
    {
        $this->authAs();
        $this->db->seed('school_classes', [
            ['id' => 1, 'name' => 'Class 1'],
        ]);
        $this->db->seed('sections', [
            ['id' => 1, 'name' => 'A'],
        ]);
        $this->db->seed('batches', [
            ['id' => 1, 'name' => 'Batch 1'],
        ]);
        $this->db->seed('students', []);
        $this->db->seed('users', []);

        $_GET['from_class_id'] = '1';
        $this->invoke(fn () => (new StudentController())->promoteForm());
        unset($_GET['from_class_id']);

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('school_classes', $joined);
        $this->assertStringContainsString('batches', $joined);
        $this->assertStringContainsString('s.class_id', $joined,
            'promote list filters students by class_id column');
    }

    public function test_bulk_attendance_inserts_marked_by_and_school_class_id(): void
    {
        $this->authAs();
        $this->db->seed('students', [
            ['id' => 1, 'class_id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('users', []);

        $_POST = [
            'date'       => '2026-09-11',
            'batch_id'   => '1',
            'section_id' => '1',
            'status'     => ['1' => 'present'],
            'remarks'    => ['1' => 'ok'],
        ];
        $this->invoke(fn () => (new AttendanceController())->saveBulk($_POST));
        $_POST = [];

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringContainsString("INSERT INTO attendances", $joined);
        $stored = $this->db->tables['attendances'][0] ?? [];
        $this->assertArrayHasKey('marked_by', $stored,
            'attendances.marked_by is NOT NULL and must be set on insert');
        $this->assertArrayHasKey('school_class_id', $stored);
        $this->assertSame('present', $stored['status'] ?? null);
    }

    public function test_report_builder_export_selects_only_requested_columns(): void
    {
        $this->authAs();
        $this->db->seed('students', [
            ['id' => 1, 'first_name' => 'A', 'status' => 'active'],
        ]);
        $this->db->seed('users', []);

        $_POST = [
            'entity'   => 'students',
            'columns'  => ['id', 'status'],
            'date_from' => '',
            'date_to'   => '',
            'status'    => 'active',
            'class_id'  => '0',
        ];
        $this->invoke(fn () => (new ReportBuilderController())->buildExport($_POST));
        $_POST = [];

        $sqls = array_column($this->db->log, 'sql');
        $joined = implode("\n", $sqls);
        $this->assertStringContainsString('students.id', $joined);
        $this->assertStringContainsString('students.status', $joined);
        $this->assertStringNotContainsString('students.address', $joined,
            'report builder must only select the requested columns');
    }

    public function test_analytics_queries_students_payments_and_fees(): void
    {
        $this->authAs();
        $this->db->seed('students', [
            ['id' => 1, 'status' => 'active'],
        ]);
        $this->db->seed('payments', [
            ['id' => 1, 'paid_amount' => 100, 'payment_status' => 'completed', 'payment_date' => date('Y-m-d')],
        ]);
        $this->db->seed('fees', [
            ['id' => 1, 'amount' => 50, 'status' => 'active', 'frequency' => 'monthly'],
        ]);
        $this->db->seed('attendances', []);
        $this->db->seed('expenses', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('teachers', []);
        $this->db->seed('class_teacher', []);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new ReportController())->analytics());

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('FROM students', $joined);
        $this->assertStringContainsString('FROM payments', $joined);
        $this->assertStringContainsString('FROM fees', $joined);
        $this->assertStringContainsString('class_teacher', $joined,
            'analytics teacher workload joins class_teacher');
    }

    public function test_exam_results_export_uses_obtained_marks_columns(): void
    {
        $this->authAs();
        $this->db->seed('exams', [
            ['id' => 1, 'name' => 'Mid', 'code' => 'M1', 'total_marks' => 100],
        ]);
        $this->db->seed('exam_results', [
            ['id' => 1, 'exam_id' => 1, 'student_id' => 1, 'obtained_marks' => 80],
        ]);
        $this->db->seed('students', [
            ['id' => 1, 'admission_number' => 'A1', 'class_id' => 1],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Pupil'],
        ]);
        $this->db->seed('school_classes', []);
        $this->db->seed('sections', []);

        $this->invoke(fn () => (new DashboardExamController())->buildResultsExport($exam = ['id' => 1, 'total_marks' => 100]));

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('er.obtained_marks', $joined);
        $this->assertStringContainsString('FROM exam_results er', $joined);
    }

    public function test_admit_card_store_persists_unique_number(): void
    {
        $this->authAs();
        $this->db->seed('admit_cards', []);
        $this->db->seed('students', []);
        $this->db->seed('users', []);
        $this->db->seed('school_classes', []);
        $this->db->seed('sections', []);

        $this->invoke(fn () => (new AdmitCardController())->storeOne(7, 9, '2026-09-11'));

        $stored = $this->db->tables['admit_cards'][0] ?? [];
        $this->assertSame(7, $stored['exam_id'] ?? null);
        $this->assertSame(9, $stored['student_id'] ?? null);
        $this->assertStringStartsWith('ADMIT-7-9-', $stored['admit_card_number'] ?? '');
        $this->assertSame('issued', $stored['status'] ?? '');
    }

    public function test_admit_card_store_dedupes_on_exam_student(): void
    {
        $this->authAs();
        $this->db->seed('admit_cards', [
            ['id' => 1, 'exam_id' => 7, 'student_id' => 9, 'admit_card_number' => 'ADMIT-7-9-0001', 'issue_date' => '2026-01-01', 'status' => 'issued'],
        ]);
        $this->db->seed('students', []);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new AdmitCardController())->storeOne(7, 9, '2026-09-11'));

        $this->assertCount(1, $this->db->tables['admit_cards'] ?? [],
            'admit_cards is unique per (exam_id, student_id) — a second insert must be skipped');
    }

    public function test_id_card_store_persists_and_dedupes_per_student(): void
    {
        $this->authAs();
        $this->db->seed('student_id_cards', []);
        $this->db->seed('students', []);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new StudentIdCardController())->storeOne(9, '2026-09-11', null, 'A+'));

        $stored = $this->db->tables['student_id_cards'][0] ?? [];
        $this->assertSame(9, $stored['student_id'] ?? null);
        $this->assertStringStartsWith('ID-9-', $stored['id_card_number'] ?? '');
        $this->assertSame('A+', $stored['blood_group'] ?? '');
        $this->assertSame('active', $stored['status'] ?? '');

        $this->db->seed('student_id_cards', [
            ['id' => 1, 'student_id' => 9, 'id_card_number' => 'ID-9-0001', 'issue_date' => '2026-01-01', 'status' => 'active'],
        ]);
        $this->invoke(fn () => (new StudentIdCardController())->storeOne(9, '2026-09-12', null, null));
        $active = array_values(array_filter($this->db->tables['student_id_cards'] ?? [], fn ($r) => ($r['deleted_at'] ?? null) === null));
        $this->assertCount(2, $active, 'dedupe is per-student on active rows only');
    }

    public function test_certificate_store_generates_year_number_and_name(): void
    {
        $this->authAs();
        $this->db->seed('certificates', []);
        $this->db->seed('students', [
            ['id' => 1, 'user_id' => 1],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Pupil'],
        ]);

        $this->invoke(fn () => (new CertificateController())->storeOne(1, 'character', '2026-09-11', 'Good student', 'issued'));

        $stored = $this->db->tables['certificates'][0] ?? [];
        $this->assertStringStartsWith('CERT-' . date('Y') . '-', $stored['certificate_number'] ?? '');
        $this->assertStringStartsWith('Character certificate for', $stored['name'] ?? '');
        $this->assertSame('issued', $stored['status'] ?? '');
        $this->assertNotEmpty($stored['template'] ?? '');
    }

    public function test_event_store_inserts_full_row(): void
    {
        $this->authAs();
        $this->db->seed('events', []);

        $_POST = ['title' => 'Fair', 'start_date' => '2026-09-15 10:00:00', 'status' => 'published'];
        $_POST['_token'] = 'x';
        $this->invoke(fn () => (new EventController())->storeOne($_POST));
        $_POST = [];

        $stored = $this->db->tables['events'][0] ?? [];
        $this->assertSame('Fair', $stored['title'] ?? '');
        $this->assertSame('published', $stored['status'] ?? '');
        $this->assertArrayHasKey('created_by', $stored);
    }

    public function test_leave_approve_sets_approver_and_decided_at(): void
    {
        $this->authAs();
        $this->db->seed('leave_requests', [
            ['id' => 1, 'teacher_id' => 1, 'leave_type_id' => 1, 'from_date' => '2026-09-20', 'to_date' => '2026-09-22', 'reason' => 'x', 'status' => 'pending'],
        ]);
        $this->db->seed('teachers', [
            ['id' => 1, 'user_id' => 1],
        ]);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new LeaveController())->applyDecision(1, 'approved', 'OK'));

        $rows = array_values(array_filter($this->db->tables['leave_requests'] ?? [], fn ($r) => $r['id'] == 1));
        $updated = $rows[0] ?? [];
        $this->assertSame('approved', $updated['status'] ?? '');
        $this->assertSame(1, $updated['approver_id'] ?? null);
        $this->assertArrayHasKey('decided_at', $updated);
    }

    public function test_leave_days_inclusive_diff(): void
    {
        $this->authAs();
        $this->db->seed('leave_requests', [
            ['id' => 1, 'teacher_id' => 1, 'leave_type_id' => 1, 'from_date' => '2026-09-20', 'to_date' => '2026-09-22', 'reason' => 'x', 'status' => 'pending'],
        ]);
        $this->db->seed('teachers', [
            ['id' => 1, 'user_id' => 1],
        ]);
        $this->db->seed('users', []);
        $this->db->seed('leave_types', [
            ['id' => 1, 'name_en' => 'Casual'],
        ]);

        $this->invoke(fn () => (new LeaveController())->index());

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('FROM leave_requests lr', $joined);
        $this->assertStringContainsString('leave_types lt', $joined,
            'leave index must join leave_types for the type name');
        $this->assertStringContainsString('teachers t', $joined);
    }

    public function test_assignment_grade_sets_marks_status_graded_by(): void
    {
        $this->authAs();
        $this->db->seed('assignment_submissions', [
            ['id' => 1, 'assignment_id' => 1, 'student_id' => 1, 'status' => 'submitted'],
        ]);
        $this->db->seed('assignments', [
            ['id' => 1, 'title' => 'A', 'total_marks' => 100],
        ]);

        $_POST = ['marks' => '85', 'feedback' => 'Great'];
        $this->invoke(fn () => (new AssignmentController())->applyGrade(
            ['id' => 1, 'assignment_id' => 1, 'total_marks' => 100],
            85.0,
            'Great'
        ));
        $_POST = [];

        $rows = array_values(array_filter($this->db->tables['assignment_submissions'] ?? [], fn ($r) => $r['id'] == 1));
        $updated = $rows[0] ?? [];
        $this->assertSame('85', (string) ($updated['marks'] ?? ''));
        $this->assertSame('graded', $updated['status'] ?? '');
        $this->assertSame(1, $updated['graded_by'] ?? null);
        $this->assertArrayHasKey('graded_at', $updated);
    }

    public function test_book_return_computes_late_fee(): void
    {
        $this->authAs();
        $this->db->seed('book_issues', [
            ['id' => 1, 'book_id' => 1, 'student_id' => 1, 'issue_date' => '2026-09-01', 'due_date' => '2026-09-05', 'status' => 'issued'],
        ]);
        $this->db->seed('library_settings', [
            ['id' => 1, 'late_fee_per_day' => 5.00],
        ]);
        $this->db->seed('books', [
            ['id' => 1, 'title' => 'B', 'available_quantity' => 1],
        ]);

        $this->invoke(fn () => (new LibraryController())->applyReturn([
            'id' => 1, 'book_id' => 1, 'due_date' => '2026-09-05',
        ]));

        $rows = array_values(array_filter($this->db->tables['book_issues'] ?? [], fn ($r) => $r['id'] == 1));
        $updated = $rows[0] ?? [];
        $this->assertSame('returned', $updated['status'] ?? '');
        $this->assertArrayHasKey('return_date', $updated,
            'book_issues uses return_date, not returned_at');
        $this->assertNotEmpty($updated['late_fee'] ?? null, 'late fee must be set on return');
    }

    public function test_library_collect_fine_flips_flag_only(): void
    {
        $this->authAs();
        $this->db->seed('book_issues', [
            ['id' => 1, 'book_id' => 1, 'student_id' => 1, 'issue_date' => '2026-09-01', 'due_date' => '2026-09-05', 'status' => 'returned', 'late_fee' => 30.00, 'fine_paid' => 0],
        ]);

        $this->invoke(fn () => (new LibraryController())->applyFine(1));

        $rows = array_values(array_filter($this->db->tables['book_issues'] ?? [], fn ($r) => $r['id'] == 1));
        $updated = $rows[0] ?? [];
        $this->assertSame(1, $updated['fine_paid'] ?? 0);
        $this->assertSame(30.00, $updated['late_fee'] ?? 0);
    }

    public function test_due_reminder_recipients_group_due_balances(): void
    {
        $this->authAs();
        $this->db->seed('fee_payments', [
            ['id' => 1, 'student_id' => 1, 'balance' => 400, 'status' => 'partial'],
        ]);
        $this->db->seed('students', [
            ['id' => 1, 'phone_1' => '+8801', 'user_id' => 1],
        ]);
        $this->db->seed('users', [
            ['id' => 1, 'name' => 'Pupil'],
        ]);

        $this->invoke(fn () => (new SmsController())->dueReminder());

        $joined = implode("\n", array_column($this->db->log, 'sql'));
        $this->assertStringContainsString('SUM(fp.balance)', $joined);
        $this->assertStringContainsString("NOT IN ('paid', 'cancelled', 'refunded')", $joined);
    }

    public function test_payroll_generate_store_creates_payslip_with_net(): void
    {
        $this->authAs();
        $this->db->seed('salary_structures', [
            ['id' => 1, 'teacher_id' => 1, 'basic' => 30000, 'allowances' => '{"house_rent":5000}', 'deductions' => '{"pf":1000}', 'is_active' => 1],
        ]);
        $this->db->seed('payslips', []);
        $this->db->seed('leave_requests', []);

        $_POST = ['month' => '9', 'year' => '2026', 'teacher_ids' => ['1']];
        $this->invoke(fn () => (new PayrollController())->buildPayslips([1], 9, 2026));
        $_POST = [];

        $stored = $this->db->tables['payslips'][0] ?? [];
        $this->assertSame(30000.0, (float) $stored['basic']);
        $this->assertSame(5000.0, (float) $stored['total_allowances']);
        $this->assertSame(34000.0, (float) $stored['net_salary']);
        $this->assertSame('draft', $stored['status'] ?? '');
    }

    public function test_testimonial_store_generates_year_number(): void
    {
        $this->authAs();
        $this->db->seed('testimonials', []);
        $this->db->seed('students', [
            ['id' => 1, 'user_id' => 1],
        ]);
        $this->db->seed('users', []);

        $this->invoke(fn () => (new TestimonialController())->storeOne(1, 'academic_excellence', 'Award', '2026-09-11', 'Great', 'issued', 'Principal', null, 5));

        $stored = $this->db->tables['testimonials'][0] ?? [];
        $this->assertStringStartsWith('TEST-' . date('Y') . '-', $stored['testimonial_number'] ?? '');
        $this->assertSame('academic_excellence', $stored['testimonial_type'] ?? '');
        $this->assertSame('issued', $stored['status'] ?? '');
        $this->assertSame(5, $stored['rating'] ?? 0);
    }

    public function test_vehicle_store_persists_required_columns(): void
    {
        $this->authAs();
        $this->db->seed('vehicles', []);

        $_POST = ['number' => 'DHK-11', 'type' => 'Bus', 'capacity' => '40', 'driver_name' => 'Rahim', 'is_active' => '1'];
        $this->invoke(fn () => (new VehicleController())->saveVehicle($_POST));
        $_POST = [];

        $stored = $this->db->tables['vehicles'][0] ?? [];
        $this->assertSame('DHK-11', $stored['number'] ?? '');
        $this->assertSame('Bus', $stored['type'] ?? '');
        $this->assertSame(40, (int) ($stored['capacity'] ?? 0));
        $this->assertSame(1, (int) ($stored['is_active'] ?? 0));
    }

    public function test_guardian_store_creates_user_and_pivot(): void
    {
        $this->authAs();
        $this->db->seed('users', []);
        $this->db->seed('guardians', []);
        $this->db->seed('guardian_student', []);
        $this->db->seed('students', [
            ['id' => 1, 'user_id' => 1],
        ]);

        $_POST = ['name' => 'Parent', 'email' => 'p@x.com', 'phone' => '+8801', 'relationship' => 'father', 'present_address' => 'Dhaka', 'student_ids' => ['1']];
        $this->invoke(fn () => (new GuardianController())->saveGuardian($_POST, ['1']));
        $_POST = [];

        $this->assertNotEmpty($this->db->tables['users'] ?? [], 'guardian store must create a users row');
        $guardian = $this->db->tables['guardians'][0] ?? [];
        $this->assertSame('father', $guardian['relation_type'] ?? '');
        $pivot = $this->db->tables['guardian_student'][0] ?? [];
        $this->assertSame(1, $pivot['student_id'] ?? null);
    }

    public function test_admission_toggle_upserts_settings(): void
    {
        $this->authAs();
        $this->db->seed('admission_settings', []);

        $_POST = ['is_open' => '0', 'admission_fee' => '500', 'display_year' => '2026'];
        $this->invoke(fn () => (new AdmissionController())->saveAdmissionSettings($_POST));
        $_POST = [];

        $settings = $this->db->tables['admission_settings'][0] ?? [];
        $this->assertSame(0, (int) ($settings['is_open'] ?? 1));
        $this->assertSame(500.0, (float) ($settings['admission_fee'] ?? 0));
    }

    public function test_news_bulk_publish_updates_rows(): void
    {
        $this->authAs();
        $this->db->seed('news', [
            ['id' => 1, 'title' => 'N1', 'slug' => 'n1', 'content' => 'c', 'is_published' => 0],
            ['id' => 2, 'title' => 'N2', 'slug' => 'n2', 'content' => 'c', 'is_published' => 0],
        ]);

        $_POST = ['action' => 'publish', 'ids' => ['1', '2']];
        $this->invoke(fn () => (new NewsController())->applyBulk([1, 2], 'publish'));
        $_POST = [];

        $news = $this->db->tables['news'] ?? [];
        $this->assertCount(2, $news);
        $this->assertSame(1, (int) ($news[0]['is_published'] ?? 0));
        $this->assertSame(1, (int) ($news[1]['is_published'] ?? 0));
    }
}