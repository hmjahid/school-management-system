<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;

class LoginController extends Controller
{
    public function show(): void
    {
        if (Auth::check()) {
            $this->redirect('/account');
        }
        $this->view('auth.login');
    }

    public function store(): void
    {
        $data = $this->validate([
            'email'    => 'required|email|max:191',
            'password' => 'required|max:72',
        ]);

        if (!Auth::attempt($data['email'], $data['password'])) {
            $this->withError('These credentials do not match our records.');
            $this->redirect('/login');
        }

        $redirect = $_GET['redirect'] ?? '';
        if ($redirect === '' || str_starts_with($redirect, '/') || str_contains($redirect, '://')) {
            $redirect = $redirect !== '' && str_starts_with($redirect, '/') ? $redirect : '/account';
        } else {
            $redirect = '/account';
        }

        if (Auth::hasRole('admin') && $redirect === '/account') {
            $redirect = '/admin';
        }

        $this->redirect($redirect);
    }
}
