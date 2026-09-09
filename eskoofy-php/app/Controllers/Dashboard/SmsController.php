<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SmsController extends Controller
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

        $total = $this->db->count('sms_logs');
        $rows = $this->db->fetchAll(
            "SELECT sl.*, u.name as sent_by_name
             FROM sms_logs sl
             LEFT JOIN users u ON sl.sent_by = u.id
             ORDER BY sl.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('dashboard.sms.index', [
            'rows'     => $rows,
            'history' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function send(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'recipients' => 'required|max:2000',
            'message'    => 'required|max:1600',
        ]);

        $recipients = array_map('trim', explode(',', $data['recipients']));
        $recipients = array_filter($recipients);

        $this->db->insert('sms_logs', [
            'recipients'  => $data['recipients'],
            'message'     => $data['message'],
            'status'      => 'sent',
            'sent_by'     => Auth::id(),
            'sent_at'     => date('Y-m-d H:i:s'),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'SMS sent to ' . count($recipients) . ' recipient(s).');
        $this->redirect('/dashboard/sms');
    }
}
