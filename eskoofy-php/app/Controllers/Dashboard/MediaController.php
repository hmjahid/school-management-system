<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Models\WebsiteMedia;

class MediaController extends Controller
{
    private const UPLOAD_DIR = 'media';

    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();

        $where = '1=1';
        $params = [];

        if (($_GET['category'] ?? '') !== '') {
            $where .= ' AND category = ?';
            $params[] = (string) $_GET['category'];
        }

        if (($_GET['search'] ?? '') !== '') {
            $like = '%' . $_GET['search'] . '%';
            $where .= ' AND (title LIKE ? OR file_path LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM website_media WHERE {$where}", $params
        )['cnt'] ?? 0);

        $perPage = 24;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $media = $this->db->fetchAll(
            "SELECT * FROM website_media
             WHERE {$where}
             ORDER BY id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $categoryRows = $this->db->fetchAll(
            "SELECT DISTINCT category FROM website_media
             WHERE category IS NOT NULL AND category != ''
             ORDER BY category ASC"
        );
        $categories = array_column($categoryRows, 'category');

        $this->view('dashboard.media.index', [
            'rows' => $this->paginateRows($media, $total, $perPage, $page, WebsiteMedia::class),
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::getInstance()->flash('error', 'Please choose a file to upload.');
            $this->back();
            return;
        }

        if ((int) $file['size'] > 20 * 1024 * 1024) {
            Session::getInstance()->flash('error', 'The file must not exceed 20MB.');
            $this->back();
            return;
        }

        $name = $file['name'];
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
        if (!in_array($extension, $allowed, true)) {
            Session::getInstance()->flash('error', 'Unsupported file type.');
            $this->back();
            return;
        }

        $dir = public_path('storage/' . self::UPLOAD_DIR);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            Session::getInstance()->flash('error', 'Unable to create upload directory.');
            $this->back();
            return;
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            Session::getInstance()->flash('error', 'Unable to store the uploaded file.');
            $this->back();
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $title = pathinfo($name, PATHINFO_FILENAME);
        }

        $this->db->insert('website_media', [
            'title'      => $title,
            'category'   => trim((string) ($_POST['category'] ?? '')) ?: null,
            'file_path'  => self::UPLOAD_DIR . '/' . $filename,
            'mime_type'  => $file['type'] ?? null,
            'file_size'  => (int) $file['size'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', __('Media uploaded.'));
        $this->back();
    }

    public function download(string $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM website_media WHERE id = ?", [$id]);
        if (!$row) {
            http_response_code(404);
            echo 'Not found';
            return;
        }
        $path = public_path($row['file_path']);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'File missing';
            return;
        }
        header('Content-Type: ' . ($row['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . ($row['title'] ?? basename($row['file_path'])) . '"');
        readfile($path);
        exit;
    }

    public function destroy(string $id): void
    {
        Auth::requireAuth();
        $row = $this->db->fetch("SELECT * FROM website_media WHERE id = ?", [$id]);
        if ($row && !empty($row['file_path'])) {
            $path = public_path(ltrim($row['file_path'], '/'));
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $this->db->delete('website_media', 'id = ?', [$id]);
        Session::getInstance()->flash('success', __('Media removed.'));
        $this->redirect('/dashboard/media');
    }
}