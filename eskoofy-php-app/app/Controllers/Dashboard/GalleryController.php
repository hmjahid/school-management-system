<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Gallery;

class GalleryController extends Controller
{
    private const UPLOAD_DIR = 'website/gallery';

    public function index(): void
    {
        Auth::requireAuth();

        $where = '1=1';
        $params = [];

        if (!empty($_GET['category'])) {
            $where .= ' AND category = ?';
            $params[] = (string) $_GET['category'];
        }

        $search = (string) ($_GET['q'] ?? '');
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (title LIKE ? OR description LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 24;
        $total = (int) (\App\Core\Database::getInstance()->fetch(
            "SELECT COUNT(*) as cnt FROM galleries WHERE {$where}", $params
        )['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $dbRows = \App\Core\Database::getInstance()->fetchAll(
            "SELECT * FROM galleries
             WHERE {$where}
             ORDER BY id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $rows = $this->paginateRows($dbRows, $total, $perPage, $page, Gallery::class);
        $categoryRows = \App\Core\Database::getInstance()->fetchAll(
            "SELECT DISTINCT category FROM galleries WHERE category IS NOT NULL AND category != '' ORDER BY category ASC"
        );
        $categories = array_column($categoryRows, 'category');

        $this->view('dashboard.gallery.index', [
            'rows'       => $rows,
            'categories' => $categories,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.gallery.create', [
            'gallery' => new Gallery(['is_published' => true]),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'       => 'required|max:255',
            'category'    => 'required|max:120',
            'description' => 'max:2000',
        ]);

        $imagePath = $this->storeImage(required: true);
        if ($imagePath === null) {
            return;
        }

        $gallery = Gallery::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'image_path'   => $imagePath,
            'category'     => $data['category'],
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ]);
        $gallery->setAttribute('exists', true);

        Session::getInstance()->flash('success', __('Gallery item saved.'));
        $this->redirect('/dashboard/gallery/' . $gallery->getKey() . '/edit');
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $gallery = Gallery::find($id);
        if (!$gallery) {
            Session::getInstance()->flash('error', 'Gallery item not found.');
            $this->redirect('/dashboard/gallery');
            return;
        }
        $gallery->setAttribute('exists', true);
        $this->view('dashboard.gallery.edit', ['gallery' => $gallery]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $gallery = Gallery::find($id);
        if (!$gallery) {
            Session::getInstance()->flash('error', 'Gallery item not found.');
            $this->redirect('/dashboard/gallery');
            return;
        }

        $data = $this->validate([
            'title'       => 'required|max:255',
            'category'    => 'required|max:120',
            'description' => 'max:2000',
        ]);

        $newPath = $this->storeImage(required: false);
        if ($newPath !== null) {
            $this->deleteImage($gallery->image_path);
            $gallery->image_path = $newPath;
        }

        $gallery->title = $data['title'];
        $gallery->category = $data['category'];
        $gallery->description = $data['description'] ?? null;
        $gallery->is_published = isset($_POST['is_published']) ? 1 : 0;
        $gallery->save();

        Session::getInstance()->flash('success', __('Gallery item updated.'));
        $this->back();
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $gallery = Gallery::find($id);
        if (!$gallery) {
            Session::getInstance()->flash('error', 'Gallery item not found.');
            $this->redirect('/dashboard/gallery');
            return;
        }

        $this->deleteImage($gallery->image_path);
        $gallery->delete();

        Session::getInstance()->flash('success', __('Gallery item deleted.'));
        $this->redirect('/dashboard/gallery');
    }

    private function storeImage(bool $required): ?string
    {
        if (empty($_FILES['image']['tmp_name']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            if ($required) {
                Session::getInstance()->flash('error', 'The image field is required.');
                $this->back();
                return null;
            }
            return null;
        }

        $file = $_FILES['image'];
        $maxBytes = 6 * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            Session::getInstance()->flash('error', 'The image must not exceed 6MB.');
            $this->back();
            return null;
        }

        if (isset($file['type']) && str_starts_with((string) $file['type'], 'image/') === false) {
            Session::getInstance()->flash('error', 'The file must be an image.');
            $this->back();
            return null;
        }

        $dir = public_path('storage/' . self::UPLOAD_DIR);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            Session::getInstance()->flash('error', 'Unable to create upload directory.');
            $this->back();
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;

        if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            Session::getInstance()->flash('error', 'Unable to store the uploaded file.');
            $this->back();
            return null;
        }

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, self::UPLOAD_DIR . '/')) {
            $fullPath = public_path('storage/' . $path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}