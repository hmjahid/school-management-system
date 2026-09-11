<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

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
        $apps = $this->db->fetchAll(
            "SELECT ja.*, c.title as job_title
             FROM job_applications ja
             LEFT JOIN careers c ON ja.career_id = c.id
             ORDER BY ja.created_at DESC LIMIT 200"
        );

        $this->view('dashboard.careers.applications', ['applications' => $apps]);
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
