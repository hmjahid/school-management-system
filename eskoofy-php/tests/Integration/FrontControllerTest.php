<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Api\ExamController;
use App\Controllers\Dashboard\AdmitCardController;
use App\Controllers\Dashboard\AssignmentController;
use App\Controllers\Dashboard\AttendanceController;
use App\Controllers\Dashboard\ExamController as DashboardExamController;
use App\Controllers\Dashboard\ExpenseCategoryController;
use App\Controllers\Dashboard\LibraryReportController;
use App\Controllers\Dashboard\PayrollController;
use App\Controllers\Dashboard\RefundController;
use App\Controllers\Dashboard\ReportController;
use App\Controllers\Dashboard\SearchController;
use App\Controllers\Dashboard\SeatPlanController;
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
}