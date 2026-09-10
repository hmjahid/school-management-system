<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class PostCategoryController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT * FROM post_categories ORDER BY sort_order ASC, name ASC"
        );

        $this->view('admin.post-categories', ['admin' => Auth::user(), 'categories' => $rows]);
    }

    public function create(): void
    {
        $this->view('admin.post-category-form', ['admin' => Auth::user(), 'category' => null]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name' => 'required|max:191',
            'slug' => 'required|max:191',
        ]);

        $slug = $this->uniqueSlug($data['slug'], null);

        $id = Database::getInstance()->insert('post_categories', [
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $_POST['description'] ?? null,
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'active'      => (int) ($_POST['active'] ?? 1) ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('admin.created_post_category', 'admin', (int) Auth::id(), ['id' => $id, 'name' => $data['name']]);

        $this->withSuccess('Category created.');
        $this->redirect('/admin/post-categories');
    }

    public function edit(int $id): void
    {
        $row = Database::getInstance()->fetch("SELECT * FROM post_categories WHERE id = ?", [$id]);
        if (!$row) {
            $this->withError('Category not found.');
            $this->redirect('/admin/post-categories');
        }

        $this->view('admin.post-category-form', ['admin' => Auth::user(), 'category' => $row]);
    }

    public function update(int $id): void
    {
        $row = Database::getInstance()->fetch("SELECT * FROM post_categories WHERE id = ?", [$id]);
        if (!$row) {
            $this->withError('Category not found.');
            $this->redirect('/admin/post-categories');
        }

        $data = $this->validate([
            'name' => 'required|max:191',
        ]);

        $slug = $this->uniqueSlug($_POST['slug'] ?? $row['slug'], $id);

        Database::getInstance()->update('post_categories', [
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $_POST['description'] ?? null,
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'active'      => (int) ($_POST['active'] ?? 0) ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.updated_post_category', 'admin', (int) Auth::id(), ['id' => $id, 'name' => $data['name']]);

        $this->withSuccess('Category updated.');
        $this->redirect('/admin/post-categories');
    }

    public function delete(int $id): void
    {
        $inUse = Database::getInstance()->fetch(
            "SELECT COUNT(*) AS total FROM posts WHERE category_id = ? AND deleted_at IS NULL",
            [$id]
        );
        if (($inUse['total'] ?? 0) > 0) {
            $this->withError('Cannot delete: posts still reference this category.');
            $this->redirect('/admin/post-categories');
        }

        Database::getInstance()->delete('post_categories', 'id = ?', [$id]);
        ActivityLog::log('admin.deleted_post_category', 'admin', (int) Auth::id(), ['id' => $id]);

        $this->withSuccess('Category deleted.');
        $this->redirect('/admin/post-categories');
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $slug = strtolower(trim($slug));
        $base = $slug;
        $i = 1;
        while (true) {
            $row = Database::getInstance()->fetch("SELECT id FROM post_categories WHERE slug = ? LIMIT 1", [$slug]);
            if (!$row || ($ignoreId !== null && (int) $row['id'] === $ignoreId)) {
                return $slug;
            }
            $i++;
            $slug = $base . '-' . $i;
            if ($i > 50) {
                return $base . '-' . bin2hex(random_bytes(3));
            }
        }
    }
}