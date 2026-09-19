<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;

class LogoutController extends Controller
{
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }
}
