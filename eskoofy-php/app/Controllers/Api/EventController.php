<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

class EventController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
        $upcoming = ($_GET['upcoming'] ?? '1') === '1';

        $where = $upcoming ? 'e.start_date >= ?' : '1=1';
        $params = $upcoming ? [date('Y-m-d')] : [];

        $rows = $db->fetchAll(
            "SELECT e.id, e.title, e.description, e.start_date, e.location, e.created_at
             FROM events e
             WHERE {$where}
             ORDER BY e.start_date ASC
             LIMIT {$limit}",
            $params
        );

        $this->success($rows, 'Events list retrieved');
    }
}