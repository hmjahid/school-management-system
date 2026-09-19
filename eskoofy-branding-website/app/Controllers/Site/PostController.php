<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Post;
use App\Models\PostCategory;

class PostController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 6;
        $total = Post::countPublished();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $this->view('site.blog', [
            'posts'       => Post::published($perPage, ($page - 1) * $perPage),
            'categories'  => PostCategory::active(),
            'currentPage' => $page,
            'lastPage'    => $lastPage,
            'total'       => $total,
        ]);
    }

    public function category(string $slug): void
    {
        $category = PostCategory::bySlug($slug);
        if (!$category) {
            $this->view('errors.404');

            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 6;
        $total = Post::countPublishedByCategory($slug);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        $this->view('site.blog', [
            'posts'       => Post::publishedByCategory($slug, $perPage, ($page - 1) * $perPage),
            'categories'  => PostCategory::active(),
            'currentPage' => $page,
            'lastPage'    => $lastPage,
            'total'       => $total,
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug): void
    {
        $post = Post::bySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            $this->view('errors.404');

            return;
        }

        Database::getInstance()->update('posts', ['views' => ((int) $post['views']) + 1], 'id = ?', [(int) $post['id']]);

        $this->view('site.post', [
            'post'   => $post,
            'recent' => Post::latest(3),
        ]);
    }
}