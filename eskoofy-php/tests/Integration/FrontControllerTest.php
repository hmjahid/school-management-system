<?php
declare(strict_types=1);

namespace Tests\Integration;

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
     */
    private function invoke(callable $fn): void
    {
        ob_start();
        try {
            $fn();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();
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
}