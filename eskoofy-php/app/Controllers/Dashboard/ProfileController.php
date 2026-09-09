<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class ProfileController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $this->view('dashboard.profile.index', ['user' => $user]);
    }

    public function update(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $data = $this->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email',
            'phone'    => 'max:50',
            'address'  => 'max:500',
            'gender'   => 'max:20',
        ]);

        $exists = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1",
            [$data['email'], $userId]
        );
        if ($exists) {
            Session::getInstance()->flash('error', 'This email is already in use.');
            $this->back();
            return;
        }

        $this->db->update('users', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'address'    => $data['address'] ?? null,
            'gender'     => $data['gender'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$userId]);

        Session::getInstance()->flash('success', 'Profile updated successfully.');
        $this->redirect('/dashboard/profile');
    }

    public function updatePassword(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $data = $this->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = $this->db->fetch("SELECT password FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user || !password_verify($data['current_password'], $user['password'])) {
            Session::getInstance()->flash('error', 'Current password is incorrect.');
            $this->back();
            return;
        }

        $this->db->update('users', [
            'password'   => Auth::hashPassword($data['password']),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$userId]);

        Session::getInstance()->flash('success', 'Password updated successfully.');
        $this->redirect('/dashboard/profile');
    }
}
