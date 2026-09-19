<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class FavoriteController extends Controller
{
    public function toggle(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $userId = (int) Auth::id();

        // Laravel-parity route: POST /dashboard/favorites/toggle with {url, label}.
        $url = $_POST['url'] ?? '';
        $base = rtrim((string) (url('/') ?? ''), '/');
        $isBasePrefixed = $base !== '' && str_starts_with($url, $base);
        if (!is_string($url) || $url === '' || (!str_starts_with($url, '/') && !$isBasePrefixed)) {
            $this->json(['message' => 'Invalid URL'], 422);
            return;
        }
        $label = mb_substr((string) ($_POST['label'] ?? ''), 0, 120) ?: null;

        $existing = $db->fetch(
            "SELECT id FROM dashboard_favorites WHERE user_id = ? AND url = ? LIMIT 1",
            [$userId, $url]
        );

        if ($existing) {
            $db->delete('dashboard_favorites', 'id = ?', [$existing['id']]);
            $this->json(['favorite' => false]);
            return;
        }

        $this->pruneToLimit($userId, 11);

        $db->insert('dashboard_favorites', [
            'user_id'    => $userId,
            'url'        => $url,
            'label'      => $label,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json(['favorite' => true]);
    }

    private function pruneToLimit(int $userId, int $max = 12): void
    {
        $db = Database::getInstance();
        $ids = $db->fetchAll(
            "SELECT id FROM dashboard_favorites WHERE user_id = ? ORDER BY updated_at DESC",
            [$userId]
        );
        if (count($ids) <= $max) {
            return;
        }
        $overflow = array_slice(array_column($ids, 'id'), $max);
        if ($overflow !== []) {
            $in = implode(',', array_map('intval', $overflow));
            $db->delete('dashboard_favorites', "user_id = ? AND id IN ({$in})", [$userId]);
        }
    }
}
