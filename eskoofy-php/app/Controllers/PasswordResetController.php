<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class PasswordResetController extends Controller
{
    public function showForm(): void
    {
        $this->view('auth.forgot_password', [], 'layouts.main');
    }

    public function sendToken(): void
    {
        $data = $this->validate([
            'email' => 'required|email',
        ]);

        $db = Database::getInstance();
        $user = $db->fetch("SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1", [$data['email']]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->delete('password_reset_tokens', 'user_id = ?', [$user['id']]);
            $db->insert('password_reset_tokens', [
                'user_id'    => $user['id'],
                'token'      => hash('sha256', $token),
                'expires_at' => $expires,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            error_log("Password reset token for {$user['email']}: {$token}");
        }

        Session::getInstance()->flash('success', 'If the email exists, a reset link has been sent.');
        $this->redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $token = $_GET['token'] ?? '';
        $this->view('auth.reset_password', ['token' => $token], 'layouts.main');
    }

    public function reset(): void
    {
        $data = $this->validate([
            'token'    => 'required',
            'password' => 'required|min:8',
        ]);

        $db = Database::getInstance();
        $tokenHash = hash('sha256', $data['token']);

        $record = $db->fetch(
            "SELECT * FROM password_reset_tokens WHERE token = ? AND expires_at > ? LIMIT 1",
            [$tokenHash, date('Y-m-d H:i:s')]
        );

        if (!$record) {
            Session::getInstance()->flash('error', 'Invalid or expired token.');
            $this->redirect('/forgot-password');
            return;
        }

        $db->update('users', [
            'password'   => \App\Core\Auth::hashPassword($data['password']),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$record['user_id']]);

        $db->delete('password_reset_tokens', 'user_id = ?', [$record['user_id']]);

        Session::getInstance()->flash('success', 'Password reset successfully. Please login.');
        $this->redirect('/login');
    }
}
