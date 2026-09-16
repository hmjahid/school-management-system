<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

/**
 * Public website-content endpoints.
 * Parity with eskoofy-app Api\WebsiteContentController (getPageContent,
 * getActivePages).
 */
class WebsiteContentController extends Controller
{
    private function requireAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            $this->error('Forbidden.', 403);
        }
    }

    public function updatePageContent(string $page): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $row  = $db->fetch("SELECT id FROM website_contents WHERE page = ? LIMIT 1", [$page]);
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['title', 'title_en', 'title_bn', 'content', 'content_en', 'content_bn', 'meta_description'] as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                $updates[$field] = is_string($value) ? $value : json_encode($value);
            }
        }
        if (isset($_POST['is_active'])) {
            $updates['is_active'] = (int) $_POST['is_active'];
        }
        if ($row) {
            $db->update('website_contents', $updates, 'id = ?', [(int) $row['id']]);
        } else {
            $updates['page'] = $page;
            $updates['created_at'] = date('Y-m-d H:i:s');
            $db->insert('website_contents', $updates);
        }
        $this->success(['page' => $page], 'Page content updated');
    }

    public function uploadImage(string $page): void
    {
        $this->requireAdmin();
        $path = trim((string) ($_POST['image_path'] ?? ''));
        if ($path === '') {
            $this->error('image_path is required.', 422);
        }
        $db = Database::getInstance();
        $row = $db->fetch("SELECT id FROM website_contents WHERE page = ? LIMIT 1", [$page]);
        $images = [];
        if ($row) {
            $images = json_decode((string) ($row['images'] ?? '[]'), true) ?: [];
        }
        $images[] = $path;
        $encoded = json_encode($images);
        if ($row) {
            $db->update('website_contents', [
                'images'     => $encoded,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $row['id']]);
        } else {
            $db->insert('website_contents', [
                'page'       => $page,
                'title'      => $page,
                'images'     => $encoded,
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->success(['path' => $path], 'Image uploaded');
    }
    public function getPageContent(string $page): void
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            "SELECT page, title, title_en, title_bn, content, content_en, content_bn,
                    meta_description, meta_description_en, meta_description_bn, is_active
             FROM website_contents WHERE page = ? LIMIT 1",
            [$page]
        );

        if (! $row) {
            $this->error('Page content not found.', 404);
        }
        if (! (int) $row['is_active']) {
            $this->error('Page content not found.', 404);
        }

        $locale = $this->locale();
        $contentKey = $locale === 'bn' && ! empty($row['content_bn'])
            ? 'content_bn'
            : ($locale === 'en' && ! empty($row['content_en']) ? 'content_en' : 'content');
        $titleKey = $locale === 'bn' && ! empty($row['title_bn'])
            ? 'title_bn'
            : ($locale === 'en' && ! empty($row['title_en']) ? 'title_en' : 'title');

        $content = json_decode((string) ($row[$contentKey] ?? ''), true);
        $this->success([
            'page'   => $row['page'],
            'title'  => $row[$titleKey] ?? $row['title'],
            'content' => is_array($content) ? $content : $row[$contentKey],
        ], 'Page content retrieved');
    }

    public function getActivePages(): void
    {
        $db  = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT page, title FROM website_contents WHERE is_active = 1 ORDER BY page"
        );
        $this->success($rows, 'Active pages retrieved');
    }

    private function locale(): string
    {
        $cookie = $_COOKIE['app_locale'] ?? 'en';
        return in_array($cookie, ['en', 'bn'], true) ? $cookie : 'en';
    }
}