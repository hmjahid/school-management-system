<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class NewsController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

        $rows = $db->fetchAll(
            "SELECT n.id, n.title, n.slug, n.content, n.published_at, n.created_at
             FROM news n
             WHERE n.is_published = 1
             ORDER BY n.published_at DESC
             LIMIT {$limit}"
        );

        $this->success($rows, 'News list retrieved');
    }

    public function categories(): void
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT category, COUNT(*) AS count FROM news
             WHERE is_published = 1 AND category IS NOT NULL AND category != ''
             GROUP BY category ORDER BY category"
        );
        $this->success($rows, 'News categories retrieved');
    }

    public function upcomingEvents(): void
    {
        $db = Database::getInstance();
        $rows = [];
        try {
            $rows = $db->fetchAll(
                "SELECT id, title, start_date, location, description
                 FROM events
                 WHERE status = 'published' AND start_date >= CURDATE() AND deleted_at IS NULL
                 ORDER BY start_date ASC LIMIT 10"
            );
        } catch (\Throwable) {
            $rows = [];
        }
        $this->success($rows, 'Upcoming events retrieved');
    }

    public function show(int $id): void
    {
        $db  = Database::getInstance();
        $row = $db->fetch(
            "SELECT n.id, n.title, n.slug, n.content, n.image_url, n.category,
                    n.author_name, n.published_at, n.created_at, n.updated_at
             FROM news n
             WHERE n.id = ? AND n.is_published = 1",
            [$id]
        );
        if (! $row) {
            $this->error('News article not found.', 404);
        }
        $this->success($row, 'News article retrieved');
    }
}