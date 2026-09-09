<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!\App\Core\Auth::check()) {
            \App\Core\Session::getInstance()->flash('error', 'Please login to continue.');
            header('Location: /login');
            exit;
        }
    }
}
