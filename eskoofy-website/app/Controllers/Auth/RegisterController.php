<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Customer;

class RegisterController extends Controller
{
    public function show(): void
    {
        if (Auth::check()) {
            $this->redirect('/account');
        }
        $this->view('auth.register');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'     => 'required|max:120',
            'email'    => 'required|email|max:191',
            'password' => 'required|min:8|max:72',
        ]);

        if (Customer::findByEmail($data['email'])) {
            $this->withError('An account with this email already exists.');
            $this->redirect('/register');
        }

        $id = Database::getInstance()->insert('customers', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Auth::hashPassword($data['password']),
            'company'    => $_POST['company'] ?? null,
            'country'    => $_POST['country'] ?? null,
            'locale'     => 'en',
            'role'       => 'customer',
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = Database::getInstance()->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
        Auth::login($user ?? ['id' => $id, 'role' => 'customer']);

        \App\Services\ActivityLog::log('customer.registered', 'customer', $id, ['email' => $data['email']]);

        $this->withSuccess('Welcome to Eskoofy! Your account is ready.');
        $this->redirect('/account');
    }
}
