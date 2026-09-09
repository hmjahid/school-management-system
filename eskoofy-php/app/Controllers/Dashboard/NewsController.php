<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class NewsController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (n.title LIKE ? OR n.content LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND n.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM news n WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT n.*, u.name as creator_name
             FROM news n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE {$where}
             ORDER BY n.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.news.index', [
            'rows'     => $rows,
            'news' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'search'   => $search,
            'status'   => $status,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'        => 'required|max:255',
            'slug'         => 'max:255',
            'content'      => 'required',
            'excerpt'      => 'max:1000',
            'status'       => 'in:draft,published',
            'is_event'     => 'numeric',
            'published_at' => '',
            'image'        => 'max:2048',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']), '-'));
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'news-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $imagePath = 'uploads/news/' . $filename;
        }

        $this->db->insert('news', [
            'title'        => $data['title'],
            'slug'         => $data['slug'],
            'content'      => $data['content'],
            'excerpt'      => $data['excerpt'] ?? null,
            'status'       => $data['status'] ?? 'draft',
            'is_event'     => $data['is_event'] ?? 0,
            'image'        => $imagePath,
            'published_at' => $data['published_at'] ?? null,
            'created_by'   => Auth::id(),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'News article created successfully.');
        $this->redirect('/dashboard/news');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $news = $this->db->fetch("SELECT * FROM news WHERE id = ? LIMIT 1", [$id]);
        if (!$news) {
            Session::getInstance()->flash('error', 'News article not found.');
            $this->redirect('/dashboard/news');
            return;
        }

        $data = $this->validate([
            'title'        => 'required|max:255',
            'slug'         => 'max:255',
            'content'      => 'required',
            'excerpt'      => 'max:1000',
            'status'       => 'in:draft,published',
            'is_event'     => 'numeric',
            'published_at' => '',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']), '-'));
        }

        $updateData = [
            'title'        => $data['title'],
            'slug'         => $data['slug'],
            'content'      => $data['content'],
            'excerpt'      => $data['excerpt'] ?? null,
            'status'       => $data['status'] ?? 'draft',
            'is_event'     => $data['is_event'] ?? 0,
            'published_at' => $data['published_at'] ?? null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'news-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $updateData['image'] = 'uploads/news/' . $filename;
        }

        $this->db->update('news', $updateData, 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'News article updated.');
        $this->redirect('/dashboard/news');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('news', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'News article deleted.');
        $this->redirect('/dashboard/news');
    }
}
