<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

/**
 * Public legal / info pages — terms, privacy, sitemap, home.
 * Parity with eskoofy-app Api\LegalController (reads website_contents).
 */
class LegalController extends Controller
{
    private function contentFor(string $page): array
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            "SELECT title, content, meta_description FROM website_contents WHERE page = ? AND is_active = 1 LIMIT 1",
            [$page]
        );
        if (! $row) {
            return ['title' => ucfirst($page), 'content' => null, 'meta_description' => null];
        }
        $content = json_decode((string) ($row['content'] ?? ''), true);
        return [
            'title'            => $row['title'] ?? ucfirst($page),
            'content'          => is_array($content) ? $content : $row['content'],
            'meta_description' => $row['meta_description'] ?? null,
        ];
    }

    public function getTerms(): void
    {
        $this->success($this->contentFor('terms'), 'Terms retrieved');
    }

    public function getPrivacy(): void
    {
        $this->success($this->contentFor('privacy'), 'Privacy policy retrieved');
    }

    public function getSitemap(): void
    {
        $db   = Database::getInstance();
        $urls = [
            ['url' => '/', 'name' => 'Home'],
            ['url' => '/about', 'name' => 'About'],
            ['url' => '/academics', 'name' => 'Academics'],
            ['url' => '/admission', 'name' => 'Admissions'],
            ['url' => '/fees', 'name' => 'Fees'],
            ['url' => '/news', 'name' => 'News'],
            ['url' => '/events', 'name' => 'Events'],
            ['url' => '/gallery', 'name' => 'Gallery'],
            ['url' => '/contact', 'name' => 'Contact'],
            ['url' => '/results', 'name' => 'Results'],
        ];
        try {
            $news = $db->fetchAll("SELECT slug FROM news WHERE is_published = 1 ORDER BY id");
            foreach ($news as $n) {
                $urls[] = ['url' => '/news/' . $n['slug'], 'name' => $n['slug']];
            }
        } catch (\Throwable) {
            //
        }
        $this->success(['urls' => $urls], 'Sitemap retrieved');
    }

    public function getHome(): void
    {
        $db = Database::getInstance();
        $data = [
            'pageTitle' => 'Home',
        ];
        try {
            $data['news'] = $db->fetchAll(
                "SELECT id, title, slug, published_at FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 6"
            );
        } catch (\Throwable) {
            $data['news'] = [];
        }
        try {
            $data['events'] = $db->fetchAll(
                "SELECT id, title, start_date, location FROM events WHERE status = 'published' AND start_date >= CURDATE() ORDER BY start_date ASC LIMIT 6"
            );
        } catch (\Throwable) {
            $data['events'] = [];
        }
        $this->success($data, 'Home data retrieved');
    }
}