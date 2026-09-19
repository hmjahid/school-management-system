<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class ActivityController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $rows = Database::getInstance()->fetchAll("SELECT * FROM activity_logs ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}");

        $this->view('admin.activities', [
            'admin'   => Auth::user(),
            'logs'    => $rows,
            'page'    => $page,
        ]);
    }
}
