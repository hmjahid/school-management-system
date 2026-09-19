<?php

declare(strict_types=1);

/**
 * Create the super_admin account with a strong, random password.
 *
 * Usage:
 *   php database/seed_admin.php                      # random password (printed once)
 *   ADMIN_PASSWORD='S3cure!Pass' php database/seed_admin.php   # explicit password
 *   ADMIN_EMAIL=admin@myschool.edu php database/seed_admin.php # custom email
 *
 * Idempotent: re-running updates nothing and does not reset an existing
 * password, so it is safe to run on an already-live database.
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;

$db = Database::getInstance();

// First ensure the super_admin role exists.
$db->insert('roles', [
    'name'       => 'super_admin',
    'guard_name' => 'web',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

$existing = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$_ENV['ADMIN_EMAIL'] ?? 'admin@eskoofy.com']);
if ($existing) {
    fwrite(STDOUT, "Admin account already exists — no changes made.\n");
    exit(0);
}

$password = $_ENV['ADMIN_PASSWORD'] ?? bin2hex(random_bytes(12));
$role = $db->fetch("SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1");
$roleId = (int) ($role['id'] ?? 1);

$db->insert('users', [
    'name'              => 'Administrator',
    'email'             => $_ENV['ADMIN_EMAIL'] ?? 'admin@eskoofy.com',
    'password'          => Auth::hashPassword($password),
    'role_id'           => $roleId,
    'email_verified_at' => date('Y-m-d H:i:s'),
    'created_at'        => date('Y-m-d H:i:s'),
    'updated_at'        => date('Y-m-d H:i:s'),
]);

fwrite(STDOUT, "Super admin created.\n");
fwrite(STDOUT, "  Email:    " . ($_ENV['ADMIN_EMAIL'] ?? 'admin@eskoofy.com') . "\n");
if ($_ENV['ADMIN_PASSWORD'] ?? null) {
    fwrite(STDOUT, "  Password: (from ADMIN_PASSWORD env)\n");
} else {
    fwrite(STDOUT, "  Password: " . $password . "\n");
    fwrite(STDOUT, "  -> Change this immediately after first login.\n");
}
exit(0);