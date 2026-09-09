<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class NoticeController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

        $rows = $db->fetchAll(
            "SELECT n.id, n.title, n.content, n.published_at, n.created_at
             FROM notices n
             WHERE n.is_published = 1
             ORDER BY n.published_at DESC, n.id DESC
             LIMIT {$limit}"
        );

        $this->success($rows, 'Notices list retrieved');
    }
}