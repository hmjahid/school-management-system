<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }
        $this->view('auth.login');
    }

    public function login(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($data['email'], $data['password'])) {
            Session::getInstance()->flash('success', 'Welcome back!');
            $this->redirect('/dashboard');
            return;
        }

        Session::getInstance()->flash('error', 'Invalid email or password.');
        $this->back();
    }

    public function logout(): void
    {
        Auth::logout();
        Session::getInstance()->flash('success', 'Logged out successfully.');
        $this->redirect('/');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }
        $this->view('auth.register');
    }

    public function register(): void
    {
        $data = $this->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $db = Database::getInstance();

        $exists = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$data['email']]);
        if ($exists) {
            Session::getInstance()->flash('error', 'This email is already registered.');
            $this->back();
            return;
        }

        $userId = $db->insert('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Auth::hashPassword($data['password']),
            'role'       => 'student',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Auth::login(['id' => $userId, 'role' => 'student']);

        Session::getInstance()->flash('success', 'Account created successfully.');
        $this->redirect('/dashboard');
    }
}
