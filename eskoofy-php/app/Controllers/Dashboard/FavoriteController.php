<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class FavoriteController extends Controller
{
    public function toggle(string $module): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        $userId = (int) Auth::id();

        $existing = $db->fetch(
            "SELECT id FROM dashboard_favorites WHERE user_id = ? AND module = ? LIMIT 1",
            [$userId, $module]
        );

        if ($existing) {
            $db->delete('dashboard_favorites', 'id = ?', [$existing['id']]);
            $favorited = false;
        } else {
            $db->insert('dashboard_favorites', [
                'user_id'    => $userId,
                'module'     => $module,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $favorited = true;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['favorited' => $favorited]);
            exit;
        }
        $this->back();
    }
}
