<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;

class HelpController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.help.index');
    }
}
