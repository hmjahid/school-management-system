<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\Sms\SmsManager;

class SmsController extends Controller
{
    private Database $db;

    private SmsManager $sms;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->sms = new SmsManager();
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

        $driver = $this->sms->driver();

        $results = [];
        $delivered = 0;
        foreach ($recipients as $recipient) {
            $result = $driver->send($recipient, $data['message']);
            $results[$recipient] = [
                'success' => $result['success'],
                'status'  => $result['status'],
                'message_id' => $result['message_id'] ?? null,
                'error'   => $result['error'] ?? null,
                'provider' => $result['provider'] ?? $driver->name(),
            ];
            if ($result['success']) {
                $delivered++;
            }
        }

        $status = $delivered === count($recipients) ? 'sent'
            : ($delivered === 0 ? 'failed' : 'partial');

        $this->db->insert('sms_logs', [
            'recipients'  => $data['recipients'],
            'message'     => $data['message'],
            'status'      => $status,
            'gateway'     => $driver->name(),
            'sms_status'  => json_encode($results, JSON_UNESCAPED_SLASHES),
            'sent_by'     => Auth::id(),
            'sent_at'     => date('Y-m-d H:i:s'),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $flash = $delivered === count($recipients)
            ? 'SMS sent to ' . count($recipients) . ' recipient(s) via ' . $driver->name() . '.'
            : ('SMS delivery incomplete: ' . $delivered . ' of ' . count($recipients) . ' delivered via ' . $driver->name() . '.');
        Session::getInstance()->flash($delivered === count($recipients) ? 'success' : 'error', $flash);
        $this->redirect('/dashboard/sms');
    }
}