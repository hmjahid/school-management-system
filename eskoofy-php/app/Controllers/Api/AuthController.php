<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\PersonalAccessToken;
use App\Models\RefreshToken;
use App\Models\User;

/**
 * JSON API authentication — Sanctum-style token pair (access + refresh).
 */
class AuthController extends Controller
{
    public function login(): void
    {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            $this->error('Email and password are required.', 422);
        }

        $row = Database::getInstance()->fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1",
            [$email]
        );

        if (! $row || ! password_verify($password, (string) $row['password'])) {
            $this->error('Invalid login details.', 401);
        }

        $user = User::newFromRow($row);
        $pair = $this->issueTokenPair($user);

        $this->success([
            'access_token'  => $pair['access_token'],
            'refresh_token' => $pair['refresh_token'],
            'token_type'    => 'Bearer',
            'expires_in'    => 60 * 60,
            'user'          => $user->toArray(),
        ], 'Login successful');
    }

    public function register(): void
    {
        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role     = trim((string) ($_POST['role'] ?? 'user'));

        if ($name === '' || $email === '' || strlen($password) < 8) {
            $this->error('Name, email and a password of at least 8 characters are required.', 422);
        }

        $db = Database::getInstance();
        if ($db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])) {
            $this->error('A user with that email already exists.', 409);
        }

        $roleId = Auth::roleId($role);
        $userId = $db->insert('users', [
            'name'              => $name,
            'email'             => $email,
            'password'          => Auth::hashPassword($password),
            'role'              => $role,
            'role_id'           => $roleId,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        try {
            $db->insert('model_has_roles', [
                'role_id'    => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $userId,
            ]);
        } catch (\Throwable) {
            // model_has_roles may not exist yet.
        }

        $user = User::find($userId);
        $pair = $this->issueTokenPair($user);

        $this->json([
            'success' => true,
            'message' => 'Registration successful',
            'data'    => [
                'access_token'  => $pair['access_token'],
                'refresh_token' => $pair['refresh_token'],
                'token_type'    => 'Bearer',
                'expires_in'    => 60 * 60,
                'user'          => $user->toArray(),
            ],
        ], 201);
    }

    public function me(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->error('Unauthenticated.', 401);
        }
        $user->load('roles');
        $this->success($user->toArray(), 'User profile retrieved');
    }

    public function logout(): void
    {
        $tokenId = (int) \App\Core\Session::getInstance()->get('api_token_id', 0);
        if ($tokenId > 0) {
            Database::getInstance()->delete('personal_access_tokens', 'id = ?', [$tokenId]);
        }
        Auth::logout();
        $this->success(['session_cleared' => true], 'Successfully logged out');
    }

    public function refreshToken(): void
    {
        $refresh = trim((string) ($_POST['refresh_token'] ?? ''));
        if ($refresh === '') {
            $this->error('Refresh token is required.', 422);
        }

        $row = Database::getInstance()->fetch(
            "SELECT * FROM refresh_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1",
            [hash('sha256', $refresh)]
        );

        if (! $row) {
            $this->error('Invalid or expired refresh token.', 401);
        }

        $user = User::find((int) $row['user_id']);
        if (! $user) {
            $this->error('User not found.', 401);
        }

        Database::getInstance()->update(
            'refresh_tokens',
            ['last_used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [(int) $row['id']]
        );

        $pair = $this->issueTokenPair($user);
        $this->success([
            'access_token'  => $pair['access_token'],
            'refresh_token' => $pair['refresh_token'],
            'token_type'    => 'Bearer',
            'expires_in'    => 60 * 60,
            'user'          => $user->toArray(),
        ], 'Token refreshed');
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function issueTokenPair(User $user): array
    {
        $access = PersonalAccessToken::createToken(
            (int) $user->getKey(),
            'auth_token',
            ['*'],
            date('Y-m-d H:i:s', time() + 60 * 60)
        );

        $plainRefresh = bin2hex(random_bytes(40));
        Database::getInstance()->insert('refresh_tokens', [
            'user_id'    => (int) $user->getKey(),
            'token'      => hash('sha256', $plainRefresh),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 190),
            'expires_at' => date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60),
        ]);

        return [
            'access_token'  => $access['plain_text_token'],
            'refresh_token' => $plainRefresh,
        ];
    }
}