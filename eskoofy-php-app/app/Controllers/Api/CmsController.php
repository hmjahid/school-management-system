<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Admin CMS API — pages, media, menus, settings, header/footer, blocks.
 * Parity with eskoofy-laravel-app Api\CmsController.
 */
class CmsController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function requireAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            $this->error('Forbidden.', 403);
        }
    }

    // ── Pages ──────────────────────────────────────────────────────────

    public function pages(): void
    {
        $this->requireAdmin();
        $rows = $this->db->fetchAll(
            "SELECT id, page, title, title_en, title_bn, is_active, updated_at
             FROM website_contents ORDER BY page"
        );
        $this->success($rows, 'CMS pages retrieved');
    }

    public function showPage(int $id): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT * FROM website_contents WHERE id = ?", [$id]);
        if (! $row) {
            $this->error('Page not found', 404);
        }
        $this->success($row, 'CMS page retrieved');
    }

    public function storePage(): void
    {
        $this->requireAdmin();
        $page  = trim((string) ($_POST['page'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($page === '' || $title === '') {
            $this->error('Page and title are required.', 422);
        }
        $content = $_POST['content'] ?? '[]';
        $id = $this->db->insert('website_contents', [
            'page'        => $page,
            'title'       => $title,
            'content'     => is_string($content) ? $content : json_encode($content),
            'is_active'   => (int) ($_POST['is_active'] ?? 1),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->json(['success' => true, 'message' => 'Page created', 'data' => ['id' => $id]], 201);
    }

    public function updatePage(int $id): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['page', 'title', 'title_en', 'title_bn', 'content', 'content_en', 'content_bn', 'meta_description'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        if (isset($_POST['is_active'])) {
            $updates['is_active'] = (int) $_POST['is_active'];
        }
        $this->db->update('website_contents', $updates, 'id = ?', [$id]);
        $this->success(['id' => $id], 'Page updated');
    }

    public function destroyPage(int $id): void
    {
        $this->requireAdmin();
        $this->db->delete('website_contents', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Page deleted');
    }

    // ── Media ──────────────────────────────────────────────────────────

    public function media(): void
    {
        $this->requireAdmin();
        $rows = $this->db->fetchAll(
            "SELECT id, title, category, file_path, mime_type, file_size, created_at
             FROM website_media ORDER BY created_at DESC LIMIT 100"
        );
        $this->success($rows, 'Media retrieved');
    }

    public function uploadMedia(): void
    {
        $this->requireAdmin();
        $title = trim((string) ($_POST['title'] ?? ''));
        $path  = trim((string) ($_POST['file_path'] ?? ''));
        if ($path === '') {
            $this->error('file_path is required.', 422);
        }
        $id = $this->db->insert('website_media', [
            'title'      => $title ?: basename($path),
            'category'   => trim((string) ($_POST['category'] ?? '')),
            'file_path'  => $path,
            'mime_type'  => trim((string) ($_POST['mime_type'] ?? '')),
            'file_size'  => (int) ($_POST['file_size'] ?? 0) ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['success' => true, 'message' => 'Media uploaded', 'data' => ['id' => $id]], 201);
    }

    public function destroyMedia(int $id): void
    {
        $this->requireAdmin();
        $this->db->delete('website_media', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Media deleted');
    }

    // ── Menus / Settings / Header / Footer / Blocks ────────────────────

    public function menus(): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT settings FROM website_settings LIMIT 1");
        $this->success(['menus' => json_decode((string) ($row['settings'] ?? '[]'), true) ?: []], 'Menus retrieved');
    }

    public function updateMenus(): void
    {
        $this->requireAdmin();
        $this->db->update('website_settings', [
            'settings'   => json_encode($_POST['menus'] ?? []),
            'updated_at' => date('Y-m-d H:i:s'),
        ], '1=1');
        $this->success([], 'Menus updated');
    }

    public function settings(): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT * FROM website_settings LIMIT 1");
        $this->success($row ?: [], 'CMS settings retrieved');
    }

    public function updateSettings(): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['school_name', 'tagline', 'address', 'city', 'established_year'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updates, 'id = ?', [(int) $existing['id']]);
        } else {
            $updates['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('website_settings', $updates);
        }
        $this->success([], 'CMS settings updated');
    }

    public function header(): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT logo_path, og_image_path, favicon_path FROM website_settings LIMIT 1");
        $this->success($row ?: [], 'Header settings retrieved');
    }

    public function updateHeader(): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['logo_path', 'og_image_path', 'favicon_path'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        $this->db->update('website_settings', $updates, '1=1');
        $this->success([], 'Header updated');
    }

    public function footer(): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT footer_logo_path, footer_logo_dark_path FROM website_settings LIMIT 1");
        $this->success($row ?: [], 'Footer settings retrieved');
    }

    public function updateFooter(): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['footer_logo_path', 'footer_logo_dark_path'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        $this->db->update('website_settings', $updates, '1=1');
        $this->success([], 'Footer updated');
    }

    public function contentBlocks(): void
    {
        $this->requireAdmin();
        $rows = $this->db->fetchAll(
            "SELECT id, page, title, content, is_active FROM website_contents WHERE page LIKE 'block-%' ORDER BY page"
        );
        $this->success($rows, 'Content blocks retrieved');
    }

    public function storeContentBlock(): void
    {
        $this->requireAdmin();
        $page  = 'block-' . trim((string) ($_POST['key'] ?? uniqid()));
        $title = trim((string) ($_POST['title'] ?? $page));
        $id = $this->db->insert('website_contents', [
            'page'       => $page,
            'title'      => $title,
            'content'    => json_encode($_POST['content'] ?? []),
            'is_active'  => (int) ($_POST['is_active'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['success' => true, 'message' => 'Block created', 'data' => ['id' => $id]], 201);
    }

    public function showContentBlock(int $id): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT * FROM website_contents WHERE id = ?", [$id]);
        if (! $row) {
            $this->error('Block not found', 404);
        }
        $this->success($row, 'Content block retrieved');
    }

    public function updateContentBlock(int $id): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (['title', 'content', 'content_en', 'content_bn'] as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        if (isset($_POST['is_active'])) {
            $updates['is_active'] = (int) $_POST['is_active'];
        }
        $this->db->update('website_contents', $updates, 'id = ?', [$id]);
        $this->success(['id' => $id], 'Block updated');
    }

    public function destroyContentBlock(int $id): void
    {
        $this->requireAdmin();
        $this->db->delete('website_contents', 'id = ?', [$id]);
        $this->success(['id' => $id], 'Block deleted');
    }
}