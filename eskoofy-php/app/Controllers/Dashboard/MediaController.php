<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class MediaController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $media = $this->db->fetchAll(
            "SELECT * FROM website_media ORDER BY created_at DESC LIMIT 200"
        );

        $this->view('dashboard.media.index', [
            'media' => $this->paginateRows($media, count($media), max(1, count($media)), 1, \App\Models\WebsiteMedia::class),
            'rows' => $this->paginateRows($media, count($media), max(1, count($media)), 1, \App\Models\WebsiteMedia::class),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (empty($_FILES['files']['tmp_name'][0])) {
            Session::getInstance()->flash('error', 'No files uploaded.');
            $this->back();
            return;
        }
        $count = 0;
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = $_FILES['files']['name'][$i];
            $uploadDir = __DIR__ . '/../../public/uploads/media/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($name));
            move_uploaded_file($tmp, $uploadDir . $filename);
            $this->db->insert('website_media', [
                'filename'   => $filename,
                'original'   => $name,
                'file_path'  => 'uploads/media/' . $filename,
                'mime_type'  => $_FILES['files']['type'][$i] ?? null,
                'size'       => $_FILES['files']['size'][$i] ?? 0,
                'uploaded_by'=> Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }
        Session::getInstance()->flash('success', "{$count} file(s) uploaded.");
        $this->redirect('/dashboard/media');
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
        $path = __DIR__ . '/../../public/' . $row['file_path'];
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'File missing';
            return;
        }
        header('Content-Type: ' . ($row['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . ($row['original'] ?? $row['filename']) . '"');
        readfile($path);
        exit;
    }

    public function destroy(string $id): void
    {
        Auth::requireAuth();
        $this->db->delete('website_media', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Media removed.');
        $this->redirect('/dashboard/media');
    }
}
