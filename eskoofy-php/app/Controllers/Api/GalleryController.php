<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;

/**
 * Public website gallery endpoints.
 * Parity with eskoofy-app Api\GalleryController (index, categories).
 */
class GalleryController extends Controller
{
    public function index(): void
    {
        $db  = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT id, title, description, image_path, category
             FROM galleries
             WHERE is_published = 1 AND deleted_at IS NULL
             ORDER BY id DESC"
        );
        $this->success($rows, 'Gallery retrieved');
    }

    public function categories(): void
    {
        $db   = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT DISTINCT category FROM galleries WHERE is_published = 1 AND deleted_at IS NULL AND category IS NOT NULL AND category != '' ORDER BY category"
        );
        $this->success(array_map(static fn (array $r) => $r['category'], $rows), 'Gallery categories retrieved');
    }
}