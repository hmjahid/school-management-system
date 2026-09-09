<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class ContactSubmissionController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $type = $_GET['type'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($type !== '') {
            $where .= ' AND type = ?';
            $params[] = $type;
        }

        $total = (int) ($this->db->fetch("SELECT COUNT(*) as cnt FROM contact_submissions WHERE {$where}", $params)['cnt'] ?? 0);
        $submissions = $this->db->fetchAll(
            "SELECT * FROM contact_submissions WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.contact_submissions.index', [
            'submissions' => $submissions,
            'rows'        => $submissions,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
            'type'        => $type,
        ]);
    }

    public function markRead(string $id): void
    {
        Auth::requireAuth();
        $this->db->update('contact_submissions', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Marked as read.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        Auth::requireAuth();
        $this->db->delete('contact_submissions', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Submission deleted.');
        $this->redirect('/dashboard/contact-submissions');
    }

    public function export(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll("SELECT * FROM contact_submissions ORDER BY created_at DESC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=contact_submissions_' . date('Ymd') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Type', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Created']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['type'], $r['name'], $r['email'], $r['phone'], $r['subject'], $r['message'], $r['created_at']]);
        }
        fclose($out);
        exit;
    }
}
