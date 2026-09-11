<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Core\Support\Collection;

class CareerController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $jobs = $this->db->fetchAll("SELECT * FROM careers ORDER BY created_at DESC LIMIT 100");

        $this->view('dashboard.careers.index', ['jobs' => $jobs]);
    }

    public function applications(): void
    {
        Auth::requireAuth();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $where = '1=1';
        $params = [];
        if (!empty($_GET['status'])) {
            $where .= ' AND ja.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['career_id'])) {
            $where .= ' AND ja.career_id = ?';
            $params[] = (int) $_GET['career_id'];
        }
        if (!empty($_GET['search'])) {
            $like = '%' . $_GET['search'] . '%';
            $where .= ' AND (ja.name LIKE ? OR ja.email LIKE ? OR ja.phone LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM job_applications ja WHERE {$where}", $params
        )['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT ja.*, c.title as job_title
             FROM job_applications ja
             LEFT JOIN careers c ON ja.career_id = c.id
             WHERE {$where}
             ORDER BY ja.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $apps = \App\Models\JobApplication::hydrate($rows);
        foreach ($apps as $app) {
            if ($app->created_at === null) {
                $app->created_at = date('Y-m-d H:i:s');
            }
        }
        $applications = $this->paginateRows($apps, $total, $perPage, $page);

        $careers = new Collection(\App\Models\Career::hydrate(
            $this->db->fetchAll("SELECT id, title FROM careers ORDER BY title")
        ));

        $this->view('dashboard.careers.applications', [
            'applications' => $applications,
            'careers'      => $careers,
        ]);
    }

    public function updateApplicationStatus(int $id): void
    {
        Auth::requireAuth();
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'], true)) {
            Session::getInstance()->flash('error', 'Invalid status.');
            $this->back();
            return;
        }

        $this->db->update('job_applications', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Application updated.');
        $this->redirect('/dashboard/careers/applications');
    }

    public function destroyApplication(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('job_applications', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Application deleted.');
        $this->redirect('/dashboard/careers/applications');
    }
}
