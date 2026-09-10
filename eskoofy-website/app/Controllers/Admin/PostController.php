<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\ActivityLog;

class PostController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT p.*, c.name AS category_name, cu.name AS author_name
             FROM posts p
             LEFT JOIN post_categories c ON c.id = p.category_id
             LEFT JOIN customers cu ON cu.id = p.author_id
             ORDER BY p.id DESC"
        );

        $this->view('admin.posts', ['admin' => Auth::user(), 'posts' => $rows]);
    }

    public function create(): void
    {
        $this->view('admin.post-form', [
            'admin'      => Auth::user(),
            'post'       => null,
            'categories' => PostCategory::active(),
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'title'   => 'required|max:191',
            'slug'    => 'required|max:191',
            'content' => 'required',
            'status'  => 'required|in:draft,published',
        ]);

        $slug = $this->uniqueSlug($data['slug'], null);

        $id = Database::getInstance()->insert('posts', [
            'category_id'     => $this->nullInt($_POST['category_id'] ?? null),
            'author_id'       => (int) Auth::id(),
            'title'           => $data['title'],
            'slug'            => $slug,
            'excerpt'         => $_POST['excerpt'] ?? null,
            'content'         => $data['content'],
            'status'          => $data['status'],
            'featured_image'  => $_POST['featured_image'] ?? null,
            'meta_title'      => $_POST['meta_title'] ?? null,
            'meta_description'=> $_POST['meta_description'] ?? null,
            'published_at'    => $this->publishedAt($data['status'], $_POST['published_at'] ?? null),
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        ActivityLog::log('admin.created_post', 'admin', (int) Auth::id(), ['id' => $id, 'title' => $data['title']]);

        $this->withSuccess('Post created.');
        $this->redirect('/admin/posts');
    }

    public function edit(int $id): void
    {
        $post = Database::getInstance()->fetch("SELECT * FROM posts WHERE id = ?", [$id]);
        if (!$post) {
            $this->withError('Post not found.');
            $this->redirect('/admin/posts');
        }

        $this->view('admin.post-form', [
            'admin'      => Auth::user(),
            'post'       => $post,
            'categories' => PostCategory::active(),
        ]);
    }

    public function update(int $id): void
    {
        $post = Database::getInstance()->fetch("SELECT * FROM posts WHERE id = ?", [$id]);
        if (!$post) {
            $this->withError('Post not found.');
            $this->redirect('/admin/posts');
        }

        $data = $this->validate([
            'title'   => 'required|max:191',
            'content' => 'required',
            'status'  => 'required|in:draft,published',
        ]);

        $slug = $this->uniqueSlug($_POST['slug'] ?? '', (int) $id);

        Database::getInstance()->update('posts', [
            'category_id'     => $this->nullInt($_POST['category_id'] ?? null),
            'title'           => $data['title'],
            'slug'            => $slug,
            'excerpt'         => $_POST['excerpt'] ?? null,
            'content'         => $data['content'],
            'status'          => $data['status'],
            'featured_image'  => $_POST['featured_image'] ?? null,
            'meta_title'      => $_POST['meta_title'] ?? null,
            'meta_description'=> $_POST['meta_description'] ?? null,
            'published_at'    => $this->publishedAt($data['status'], $_POST['published_at'] ?? $post['published_at']),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.updated_post', 'admin', (int) Auth::id(), ['id' => $id, 'title' => $data['title']]);

        $this->withSuccess('Post updated.');
        $this->redirect('/admin/posts');
    }

    public function delete(int $id): void
    {
        $post = Database::getInstance()->fetch("SELECT id FROM posts WHERE id = ?", [$id]);
        if (!$post) {
            $this->withError('Post not found.');
            $this->redirect('/admin/posts');
        }

        Database::getInstance()->update('posts', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.deleted_post', 'admin', (int) Auth::id(), ['id' => $id]);

        $this->withSuccess('Post deleted.');
        $this->redirect('/admin/posts');
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $slug = strtolower(trim($slug));
        $base = $slug;
        $i = 1;
        while (true) {
            $row = Database::getInstance()->fetch("SELECT id FROM posts WHERE slug = ? LIMIT 1", [$slug]);
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

    private function publishedAt(string $status, ?string $input): ?string
    {
        if ($status !== 'published') {
            return null;
        }
        if ($input !== null && $input !== '') {
            $ts = strtotime($input);
            if ($ts !== false) {
                return date('Y-m-d H:i:s', $ts);
            }
        }

        return date('Y-m-d H:i:s');
    }

    private function nullInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}