<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Visitor;

class VisitorController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $path = trim((string) ($_GET['path'] ?? ''));
        $hideBots = ($_GET['bots'] ?? '') !== 'show';

        $this->view('admin.visitors', [
            'admin'        => Auth::user(),
            'kpis'         => Visitor::kpis(),
            'trend'        => Visitor::trend(30),
            'topPaths'     => Visitor::topPaths(10),
            'topCountries' => Visitor::topCountries(8),
            'logs'         => Visitor::paginate($page, 25, ['path' => $path, 'bots' => !$hideBots]),
            'filters'      => ['path' => $path, 'hide_bots' => $hideBots],
        ]);
    }
}
