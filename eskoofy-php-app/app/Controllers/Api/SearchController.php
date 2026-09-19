<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

/**
 * Authenticated global search (students/teachers/classes).
 * Parity with eskoofy-laravel-app Api\SearchController.
 */
class SearchController extends Controller
{
    public function search(): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            $this->success([], 'Search retrieved');
            return;
        }

        $db   = Database::getInstance();
        $like = '%' . $query . '%';
        $user = Auth::user();
        $results = [];

        $isStaff = $user && ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('teacher'));
        if ($isStaff) {
            $rows = $db->fetchAll(
                "SELECT s.id, u.name FROM students s JOIN users u ON u.id = s.user_id
                 WHERE u.deleted_at IS NULL AND u.name LIKE ? LIMIT 5",
                [$like]
            );
            foreach ($rows as $r) {
                $results[] = ['id' => (int) $r['id'], 'type' => 'student', 'name' => $r['name']];
            }
        }

        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        if ($isAdmin) {
            $rows = $db->fetchAll(
                "SELECT t.id, u.name FROM teachers t JOIN users u ON u.id = t.user_id
                 WHERE u.deleted_at IS NULL AND u.name LIKE ? LIMIT 5",
                [$like]
            );
            foreach ($rows as $r) {
                $results[] = ['id' => (int) $r['id'], 'type' => 'teacher', 'name' => $r['name']];
            }
        }

        $rows = $db->fetchAll(
            "SELECT id, name FROM school_classes WHERE deleted_at IS NULL AND name LIKE ? LIMIT 5",
            [$like]
        );
        foreach ($rows as $r) {
            $results[] = ['id' => (int) $r['id'], 'type' => 'class', 'name' => $r['name']];
        }

        $this->success($results, 'Search retrieved');
    }

    public function searchResource(string $resource): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        if (strlen($query) < 2) {
            $this->success([], 'Search retrieved');
            return;
        }

        $db   = Database::getInstance();
        $like = '%' . $query . '%';
        $user = Auth::user();

        switch ($resource) {
            case 'students':
                $allowed = $user && ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('teacher'));
                $rows = $allowed
                    ? $db->fetchAll("SELECT s.id, u.name FROM students s JOIN users u ON u.id = s.user_id WHERE u.deleted_at IS NULL AND u.name LIKE ? LIMIT 10", [$like])
                    : [];
                $this->success($rows, 'Students retrieved');
                return;
            case 'teachers':
                $allowed = $user && ($user->hasRole('admin') || $user->hasRole('super-admin'));
                $rows = $allowed
                    ? $db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON u.id = t.user_id WHERE u.deleted_at IS NULL AND u.name LIKE ? LIMIT 10", [$like])
                    : [];
                $this->success($rows, 'Teachers retrieved');
                return;
            case 'classes':
                $rows = $db->fetchAll("SELECT id, name FROM school_classes WHERE deleted_at IS NULL AND name LIKE ? LIMIT 10", [$like]);
                $this->success($rows, 'Classes retrieved');
                return;
            default:
                $this->error('Unknown search resource.', 404);
        }
    }
}