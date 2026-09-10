<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function table(): string
    {
        return $_ENV['AUTH_TABLE'] ?? 'customers';
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::getInstance()->fetch(
            "SELECT * FROM " . self::table() . " WHERE email = ? AND status = 'active' AND deleted_at IS NULL LIMIT 1",
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            Session::getInstance()->set('user_id', $user['id']);
            Session::getInstance()->set('user_role', $user['role'] ?? 'customer');
            Session::getInstance()->regenerate();
            return true;
        }
        return false;
    }

    public static function login(array $user): void
    {
        Session::getInstance()->set('user_id', $user['id']);
        Session::getInstance()->set('user_role', $user['role'] ?? 'customer');
        Session::getInstance()->regenerate();
    }

    public static function logout(): void
    {
        Session::getInstance()->destroy();
    }

    public static function check(): bool
    {
        return Session::getInstance()->has('user_id');
    }

    public static function id(): ?int
    {
        return Session::getInstance()->get('user_id');
    }

    public static function role(): ?string
    {
        return Session::getInstance()->get('user_role');
    }

    public static function user(): ?array
    {
        $id = self::id();
        if (!$id) {
            return null;
        }

        return Database::getInstance()->fetch(
            "SELECT * FROM " . self::table() . " WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Session::getInstance()->flash('error', 'Please login to continue.');
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function createToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
