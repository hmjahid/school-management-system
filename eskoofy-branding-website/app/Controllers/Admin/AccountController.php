<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class AccountController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->view('admin.account', ['admin' => Auth::user()]);
    }

    public function updateProfile(): void
    {
        $db = Database::getInstance();
        $id = (int) Auth::id();

        $data = $this->validate([
            'name'  => 'required|max:120',
            'email' => 'required|email|max:191',
        ]);

        $exists = $db->fetch(
            "SELECT id FROM customers WHERE email = ? AND id <> ? AND deleted_at IS NULL",
            [$data['email'], $id]
        );
        if ($exists) {
            $this->withError('That email address is already in use.');
            $this->redirect('/admin/account');
        }

        $db->update('customers', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $user = $db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
        if ($user) {
            Auth::login($user);
        }

        ActivityLog::log('admin.updated_own_profile', 'admin', $id);

        $this->withSuccess('Admin profile updated.');
        $this->redirect('/admin/account');
    }

    public function updatePassword(): void
    {
        $data = $this->validate([
            'current_password'          => 'required|max:72',
            'new_password'              => 'required|min:8|max:72|confirmed',
            'new_password_confirmation' => 'required|max:72',
        ]);

        $admin = Auth::user();
        if (!password_verify($data['current_password'], $admin['password'])) {
            $this->withError('Your current password is incorrect.');
            $this->redirect('/admin/account');
        }

        Database::getInstance()->update('customers', [
            'password'   => Auth::hashPassword($data['new_password']),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) Auth::id()]);

        ActivityLog::log('admin.changed_own_password', 'admin', (int) Auth::id());

        $this->withSuccess('Admin password changed.');
        $this->redirect('/admin/account');
    }
}