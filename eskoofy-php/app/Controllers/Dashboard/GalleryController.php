<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class GalleryController extends Controller
{
    private DatabaseInterface $db;

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

        $where = '1=1';
        $params = [];
        if (!empty($_GET['category'])) {
            $where .= ' AND g.category = ?';
            $params[] = $_GET['category'];
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM galleries g WHERE {$where}", $params
        )['cnt'] ?? 0);

        $albums = $this->db->fetchAll(
            "SELECT g.* FROM galleries g
             WHERE {$where}
             ORDER BY g.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $photos = array_map(function ($item) {
            return [
                'id'     => $item['id'],
                'image'  => $item['image_path'],
                'title'  => $item['title'],
                'suffix' => $item['title'],
            ];
        }, $albums);

        $categoryRows = $this->db->fetchAll("SELECT DISTINCT category FROM galleries WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $cats = array_map(function ($c) {
            return ['slug' => slugify($c['category']), 'name' => $c['category'], 'id' => $c['category']];
        }, $categoryRows);

        $this->view('dashboard.galleries.index', [
            'albums'        => $albums,
            'images'        => $photos,
            'photos'        => $photos,
            'categories'    => $cats,
            'categorySlug'  => $_GET['category'] ?? '',
            'is_published'  => true,
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
            'lastPage'      => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'        => 'required|max:255',
            'category'     => 'max:191',
            'description'  => 'max:2000',
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
                if (isset($files['error'][$i]) && $files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                if (is_string($files['name']) && $i === 0 && $files['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $ext = pathinfo(is_array($files['name']) ? $files['name'][$i] : $files['name'], PATHINFO_EXTENSION);
                $filename = 'gallery-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                move_uploaded_file(is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'], $uploadDir . $filename);

                $this->db->insert('galleries', [
                    'title'        => $i === 0 ? $data['title'] : $data['title'] . ' (' . ($i + 1) . ')',
                    'description'  => $data['description'] ?? null,
                    'image_path'   => 'uploads/gallery/' . $filename,
                    'category'     => $data['category'] ?? null,
                    'is_published' => 1,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                $uploaded++;
            }
        }

        Session::getInstance()->flash('success', "Uploaded {$uploaded} photo(s).");
        $this->redirect('/dashboard/galleries');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $image = $this->db->fetch("SELECT * FROM galleries WHERE id = ? LIMIT 1", [$id]);
        if ($image && !empty($image['image_path'])) {
            $fullPath = __DIR__ . '/../../public/' . $image['image_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $this->db->delete('galleries', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Photo removed.');
        $this->redirect('/dashboard/galleries');
    }
}