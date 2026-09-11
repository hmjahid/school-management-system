<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class NewsController extends Controller
{
    private DatabaseInterface $db;

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
        if ($status === 'published') {
            $where .= ' AND n.is_published = 1';
        } elseif ($status === 'draft') {
            $where .= ' AND n.is_published = 0';
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM news n WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT n.* FROM news n
             WHERE {$where}
             ORDER BY n.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $rows = array_map(function ($row) {
            $row['status'] = !empty($row['is_published']) ? 'published' : 'draft';
            return $row;
        }, $rows);

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
            'title'          => 'required|max:255',
            'slug'           => 'max:255',
            'content'        => 'required',
            'category'       => 'max:191',
            'status'         => 'in:draft,published',
            'is_event'       => 'numeric',
            'published_at'   => '',
            'event_date'     => '',
            'event_location' => 'max:191',
            'image'          => 'max:2048',
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

        $isPublished = isset($data['status']) && $data['status'] === 'published' ? 1 : (!empty($_POST['is_published']) ? 1 : 0);
        $publishedAt = $isPublished ? ($data['published_at'] ?: date('Y-m-d H:i:s')) : null;

        $this->db->insert('news', [
            'title'          => $data['title'],
            'slug'           => $data['slug'],
            'content'        => $data['content'],
            'image_url'      => $imagePath,
            'category'       => $data['category'] ?? null,
            'is_published'   => $isPublished,
            'is_event'       => !empty($_POST['is_event']) ? 1 : ($data['is_event'] ?? 0),
            'published_at'   => $publishedAt,
            'event_date'     => $data['event_date'] ?? null,
            'event_location' => $data['event_location'] ?? null,
            'author_name'    => $data['author_name'] ?? (Auth::user()['name'] ?? 'Admin'),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
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
            'title'          => 'required|max:255',
            'slug'           => 'max:255',
            'content'        => 'required',
            'category'       => 'max:191',
            'status'         => 'in:draft,published',
            'is_event'       => 'numeric',
            'published_at'   => '',
            'event_date'     => '',
            'event_location' => 'max:191',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']), '-'));
        }

        $updateData = [
            'title'          => $data['title'],
            'slug'           => $data['slug'],
            'content'        => $data['content'],
            'category'       => $data['category'] ?? null,
            'is_published'   => ($data['status'] ?? 'draft') === 'published' ? 1 : 0,
            'is_event'       => $data['is_event'] ?? 0,
            'published_at'   => $data['published_at'] ?? null,
            'event_date'     => $data['event_date'] ?? null,
            'event_location' => $data['event_location'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'news-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $updateData['image_url'] = 'uploads/news/' . $filename;
        }

        $this->db->update('news', $updateData, 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'News article updated.');
        $this->redirect('/dashboard/news');
    }

public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('news', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'News deleted.');
        $this->redirect('/dashboard/news');
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.news.create');
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $news = $this->db->fetch("SELECT * FROM news WHERE id = ? LIMIT 1", [$id]);
        if (!$news) {
            Session::getInstance()->flash('error', 'News item not found.');
            $this->redirect('/dashboard/news');
            return;
        }
        $this->view('dashboard.news.edit', ['news' => $news]);
    }

    public function bulk(): void
    {
        Auth::requireAuth();
        $ids = $_POST['ids'] ?? [];
        $action = $_POST['action'] ?? '';
        if (empty($ids) || !in_array($action, ['delete', 'publish', 'unpublish'], true)) {
            Session::getInstance()->flash('error', 'Invalid bulk action.');
            $this->redirect('/dashboard/news');
            return;
        }

        $count = $this->applyBulk(array_map('intval', $ids), $action);

        Session::getInstance()->flash('success', 'Bulk action applied to ' . $count . ' news item(s).');
        $this->redirect('/dashboard/news');
    }

    public function applyBulk(array $ids, string $action): int
    {
        $in = implode(',', array_map('intval', $ids));
        if ($action === 'delete') {
            $this->db->delete('news', "id IN ({$in})");
        } elseif ($action === 'publish') {
            $this->db->update('news', ['is_published' => 1, 'published_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], "id IN ({$in})");
        } else {
            $this->db->update('news', ['is_published' => 0, 'published_at' => null, 'updated_at' => date('Y-m-d H:i:s')], "id IN ({$in})");
        }
        return count($ids);
    }
}
