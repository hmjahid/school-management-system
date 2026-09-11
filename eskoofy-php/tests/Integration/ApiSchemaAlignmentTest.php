<?php
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Guards the public API controllers' SQL against schema drift.
 *
 * The API controllers echo JSON and `exit`, so they cannot be driven
 * through the SQL-logging integration harness like the site/dashboard
 * controllers can. Instead we assert at source level that the aligned
 * column names do not regress:
 *
 *   - events        uses start_date, never event_date
 *   - news          uses content + is_published, never excerpt / status
 *   - notices       has no published_at / is_published columns at all
 */
class ApiSchemaAlignmentTest extends PHPUnitTestCase
{
    private function sourceOf(string $controller): string
    {
        $path = dirname(__DIR__, 2) . '/app/Controllers/Api/' . $controller . '.php';
        $this->assertFileExists($path);
        return (string) file_get_contents($path);
    }

    public function test_events_api_uses_start_date_not_event_date(): void
    {
        $src = $this->sourceOf('EventController');
        $this->assertStringNotContainsString('event_date', $src,
            'events schema has start_date, not event_date');
        $this->assertStringContainsString('start_date', $src);
    }

    public function test_news_api_uses_content_and_is_published(): void
    {
        $src = $this->sourceOf('NewsController');
        $this->assertStringNotContainsString('n.excerpt', $src,
            'news schema has content, not excerpt');
        $this->assertStringNotContainsString("status = 'published'", $src,
            'news schema has is_published, not a status column');
        $this->assertStringContainsString('is_published', $src);
        $this->assertStringContainsString('content', $src);
    }

    public function test_notices_api_never_uses_published_at(): void
    {
        $src = $this->sourceOf('NoticeController');
        $this->assertStringNotContainsString('published_at', $src,
            'notices has no published_at/is_published columns — it uses pinned + created_at');
        $this->assertStringNotContainsString('is_published', $src);
        $this->assertStringContainsString('pinned', $src);
    }
}