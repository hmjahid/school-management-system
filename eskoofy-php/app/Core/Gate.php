<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gate stands in for Spatie's permission gates (@can) so Blade templates can
 * check permissions the same way as the Laravel app. Admins bypass all checks;
 * other roles resolve via role_has_permissions / model_has_roles.
 */
class Gate
{
    protected static ?array $rolePermissions = null;

    public static function allows(string $ability, mixed $arguments = []): bool
    {
        if (!Auth::check()) {
            return false;
        }
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        $role = $user['role'] ?? Auth::role();
        if (in_array($role, ['admin', 'super_admin', 'owner', 'Administrator'], true)) {
            return true;
        }
        $permissions = self::permissionsForRole($role);
        if (in_array($ability, $permissions, true)) {
            return true;
        }
        // model_has_permissions direct grants (user_id → permission)
        if (isset($user['id'])) {
            $row = Database::getInstance()->fetch(
                "SELECT COUNT(*) AS cnt FROM model_has_permissions p
                 JOIN permissions pm ON pm.id = p.permission_id
                 WHERE p.model_id = ? AND pm.name = ? AND p.model_type = 'App\\\\Models\\\\User'",
                [(int) $user['id'], $ability]
            );
            if ((int) ($row['cnt'] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    public static function any(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (self::allows($ability)) {
                return true;
            }
        }
        return false;
    }

    public static function none(array $abilities): bool
    {
        return !self::any($abilities);
    }

    public static function authorize(string $ability, mixed $arguments = []): void
    {
        if (!self::allows($ability, $arguments)) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * @return list<string>
     */
    protected static function permissionsForRole(?string $role): array
    {
        if ($role === null) {
            return [];
        }
        if (self::$rolePermissions !== null) {
            return self::$rolePermissions[$role] ?? [];
        }
        self::$rolePermissions = [];
        try {
            $rows = Database::getInstance()->fetchAll(
                "SELECT r.name AS role_name, pm.name AS permission_name
                 FROM role_has_permissions rhp
                 JOIN roles r ON r.id = rhp.role_id
                 JOIN permissions pm ON pm.id = rhp.permission_id"
            );
            foreach ($rows as $row) {
                self::$rolePermissions[$row['role_name']][] = $row['permission_name'];
            }
        } catch (\Throwable) {
            // Schema may not be imported during tests — treat as no permissions.
        }
        return self::$rolePermissions[$role] ?? [];
    }
}