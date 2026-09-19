<?php
declare(strict_types=1);

/**
 * Demo seeder — populates the database with the accounts documented in
 * docs/operations/DEMO-CREDENTIALS.md. Run once after schema.sql:
 *
 *   php database/seed_demo.php
 *
 * Idempotent: uses INSERT ... ON DUPLICATE KEY UPDATE so it can be re-run
 * safely. Passwords are bcrypt-hashed with cost 12.
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

// Security guard: demo accounts (weak, documented passwords) must never land
// on a production database. Require an explicit non-production environment.
$env = strtolower((string) ($_ENV['APP_ENV'] ?? ''));
$forced = in_array('--i-am-sure', $argv ?? [], true);
if ($env === 'production' && !$forced) {
    fwrite(STDERR, "Refusing to seed demo accounts in a production environment.\n");
    fwrite(STDERR, "Set APP_ENV to a non-production value, or pass --i-am-sure to override.\n");
    exit(1);
}

$db = Database::getInstance();

// ─── Roles ────────────────────────────────────────────────────────────────
$roles = [
    ['name' => 'super_admin', 'description' => 'Full system access'],
    ['name' => 'admin',       'description' => 'School administrator'],
    ['name' => 'teacher',     'description' => 'Teaching staff'],
    ['name' => 'accountant',  'description' => 'Accounts & finance'],
    ['name' => 'librarian',   'description' => 'Library management'],
    ['name' => 'student',     'description' => 'Student'],
    ['name' => 'guardian',    'description' => 'Parent or guardian'],
];

foreach ($roles as $role) {
    $existing = $db->fetch("SELECT id FROM roles WHERE name = ? LIMIT 1", [$role['name']]);
    if ($existing) {
        $db->update('roles', $role, 'id = ?', [$existing['id']]);
    } else {
        $db->insert('roles', $role + ['guard_name' => 'web']);
    }
}

$roleIds = [];
foreach ($roles as $role) {
    $row = $db->fetch("SELECT id FROM roles WHERE name = ? LIMIT 1", [$role['name']]);
    $roleIds[$role['name']] = (int) $row['id'];
}

// ─── Password helper ───────────────────────────────────────────────────────
function hashPw(string $pw): string {
    return password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ─── Demo accounts (from docs/operations/DEMO-CREDENTIALS.md) ─────────────────────────
$accounts = [
    // Admin
    ['name' => 'Super Administrator', 'email' => 'admin@school.com',    'password' => 'ChangeMe!2026$Tr0ng', 'role' => 'admin',       'role_id' => $roleIds['admin']],
    ['name' => 'School Principal',   'email' => 'principal@school.com', 'password' => 'principal123',       'role' => 'admin',       'role_id' => $roleIds['admin']],

    // Named staff
    ['name' => 'John Smith',         'email' => 'teacher.john@school.com',  'password' => 'teach1234',     'role' => 'teacher',     'role_id' => $roleIds['teacher']],
    ['name' => 'Sarah Johnson',      'email' => 'teacher.sarah@school.com', 'password' => 'teach5678',     'role' => 'teacher',     'role_id' => $roleIds['teacher']],
    ['name' => 'Demo Accountant',    'email' => 'accountant@school.com',    'password' => 'accountant123', 'role' => 'accountant',  'role_id' => $roleIds['accountant']],
    ['name' => 'Demo Librarian',     'email' => 'librarian@school.com',     'password' => 'librarian123',  'role' => 'librarian',   'role_id' => $roleIds['librarian']],
];

$now = date('Y-m-d H:i:s');

foreach ($accounts as $acct) {
    $existing = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$acct['email']]);
    $data = [
        'name'       => $acct['name'],
        'email'      => $acct['email'],
        'password'   => hashPw($acct['password']),
        'role'       => $acct['role'],
        'role_id'    => $acct['role_id'],
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('users', $data, 'id = ?', [$existing['id']]);
    } else {
        $data['created_at'] = $now;
        $db->insert('users', $data);
    }
}

// ─── Bulk demo accounts ─────────────────────────────────────────────────────
for ($i = 1; $i <= 30; $i++) {
    $email = "teacher{$i}@school.com";
    $existing = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
    $data = [
        'name'       => "Teacher {$i}",
        'email'      => $email,
        'password'   => hashPw('password'),
        'role'       => 'teacher',
        'role_id'    => $roleIds['teacher'],
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('users', $data, 'id = ?', [$existing['id']]);
    } else {
        $data['created_at'] = $now;
        $db->insert('users', $data);
    }
}

for ($i = 1; $i <= 5; $i++) {
    $email = "student{$i}@school.com";
    $existing = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
    $data = [
        'name'       => "Student {$i}",
        'email'      => $email,
        'password'   => hashPw('password'),
        'role'       => 'student',
        'role_id'    => $roleIds['student'],
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('users', $data, 'id = ?', [$existing['id']]);
    } else {
        $data['created_at'] = $now;
        $db->insert('users', $data);
    }
}

for ($i = 1; $i <= 10; $i++) {
    $email = "parent{$i}@school.com";
    $existing = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
    $data = [
        'name'       => "Parent {$i}",
        'email'      => $email,
        'password'   => hashPw('password'),
        'role'       => 'guardian',
        'role_id'    => $roleIds['guardian'],
        'updated_at' => $now,
    ];
    if ($existing) {
        $db->update('users', $data, 'id = ?', [$existing['id']]);
    } else {
        $data['created_at'] = $now;
        $db->insert('users', $data);
    }
}

// ─── Fix the orphaned admin user if it still has NULL role ─────────────────
$db->query("UPDATE users SET role = 'admin', role_id = ? WHERE role IS NULL AND deleted_at IS NULL", [$roleIds['admin']]);

echo "Demo seed complete.\n";
echo "Roles: " . count($roles) . "\n";

$userCount = (int) $db->fetch("SELECT COUNT(*) AS c FROM users WHERE deleted_at IS NULL")['c'];
echo "Users: {$userCount}\n";
echo "\nNext: run `php database/seed_demo_content.php` to seed the demo school content\n";
echo "(classes, teachers, students, guardians, notices, events, galleries, fees) — the same\n";
echo "demo data as the Laravel app and the WordPress theme.\n";
