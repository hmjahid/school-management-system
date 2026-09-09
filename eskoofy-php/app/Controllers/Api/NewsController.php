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
            "SELECT n.id, n.title, n.slug, n.excerpt, n.content, n.published_at, n.created_at
             FROM news n
             WHERE n.status = 'published'
             ORDER BY n.published_at DESC
             LIMIT {$limit}"
        );

        $this->success($rows, 'News list retrieved');
    }
}