<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class GalleryController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = $this->db->count('gallery_albums');
        $albums = $this->db->fetchAll(
            "SELECT a.*,
                (SELECT COUNT(*) FROM gallery_images WHERE album_id = a.id) as image_count
             FROM gallery_albums a
             ORDER BY a.sort_order ASC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $images = $this->db->fetchAll(
            "SELECT gi.*, ga.title as album_title
             FROM gallery_images gi
             LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
             ORDER BY gi.id DESC LIMIT 200"
        );

        $this->view('dashboard.galleries.index', [
            'albums'    => $albums,
            'images'    => $images,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'album_id'  => 'required|numeric',
            'caption'   => 'max:500',
            'sort_order' => 'numeric',
        ]);

        $uploadDir = __DIR__ . '/../../public/uploads/gallery/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploaded = 0;
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = 'gallery-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename);

                $this->db->insert('gallery_images', [
                    'album_id'   => $data['album_id'],
                    'path'       => 'uploads/gallery/' . $filename,
                    'caption'    => $data['caption'] ?? null,
                    'sort_order' => ($data['sort_order'] ?? 0) + $i,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $uploaded++;
            }
        }

        Session::getInstance()->flash('success', "Uploaded {$uploaded} image(s).");
        $this->redirect('/dashboard/galleries');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $image = $this->db->fetch("SELECT * FROM gallery_images WHERE id = ? LIMIT 1", [$id]);
        if ($image && !empty($image['path'])) {
            $fullPath = __DIR__ . '/../../public/' . $image['path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $this->db->delete('gallery_images', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Image removed.');
        $this->redirect('/dashboard/galleries');
    }
}
