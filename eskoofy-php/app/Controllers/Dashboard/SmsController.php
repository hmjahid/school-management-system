<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Services\Sms\SmsManager;

class SmsController extends Controller
{
    private DatabaseInterface $db;

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

    public function compose(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");
        $sections = $this->db->fetchAll("SELECT id, name FROM sections ORDER BY name ASC");
        $roles = $this->db->fetchAll("SELECT id, name FROM roles ORDER BY name ASC");
        $users = $this->db->fetchAll(
            "SELECT id, name, phone, role_id FROM users WHERE phone IS NOT NULL AND phone != '' ORDER BY name ASC"
        );
        $students = $this->db->fetchAll(
            "SELECT s.id, s.user_id, u.name,
                COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) as phone
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) IS NOT NULL
             ORDER BY u.name ASC LIMIT 500"
        );

        $this->view('dashboard.sms.compose', [
            'classes'  => $classes,
            'sections' => $sections,
            'roles'    => $roles,
            'users'    => $users,
            'students' => $students,
        ]);
    }

    public function preview(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'         => 'required|max:191',
            'audience_type'=> 'required|max:32',
            'message'      => 'required|max:1000',
            'school_class_id'=> 'numeric',
            'section_id'   => 'numeric',
            'role_name'    => 'max:50',
        ]);

        $recipients = $this->resolveRecipients($data);
        if (empty($recipients)) {
            Session::getInstance()->flash('error', 'No recipients match the selected audience.');
            $this->back();
            return;
        }

        $this->view('dashboard.sms.preview', [
            'data'       => $data,
            'recipients' => array_slice($recipients, 0, 50),
            'total'      => count($recipients),
        ]);
    }

    public function sendCampaign(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'         => 'required|max:191',
            'audience_type'=> 'required|max:32',
            'message'      => 'required|max:1000',
            'school_class_id'=> 'numeric',
            'section_id'   => 'numeric',
            'role_name'    => 'max:50',
            'scheduled_at' => 'max:30',
        ]);

        $recipients = $this->resolveRecipients($data);
        if (empty($recipients)) {
            Session::getInstance()->flash('error', 'No recipients match the selected audience.');
            $this->back();
            return;
        }

        $scheduledAt = !empty($data['scheduled_at']) ? $data['scheduled_at'] : null;
        $campaignId = $this->db->insert('sms_campaigns', [
            'name'           => $data['name'],
            'audience_type'  => $data['audience_type'],
            'school_class_id'=> $data['school_class_id'] ?? null,
            'section_id'     => $data['section_id'] ?? null,
            'message'        => $data['message'],
            'scheduled_at'   => $scheduledAt,
            'status'         => $scheduledAt ? 'scheduled' : 'draft',
            'created_by'     => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        foreach ($recipients as $r) {
            $this->db->insert('sms_campaign_recipients', [
                'sms_campaign_id' => $campaignId,
                'phone'           => $r['phone'],
                'user_type'       => $r['user_type'] ?? null,
                'user_id'         => $r['user_id'] ?? null,
                'status'          => 'queued',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        Session::getInstance()->flash('success', 'Campaign ' . ($scheduledAt ? 'scheduled' : 'queued') . ' for ' . count($recipients) . ' recipients.');
        $this->redirect('/dashboard/sms');
    }

    public function templates(): void
    {
        Auth::requireAuth();
        $templates = $this->db->fetchAll(
            "SELECT * FROM notification_templates WHERE sms_content IS NOT NULL AND sms_content != '' ORDER BY name ASC"
        );

        $this->view('dashboard.sms.templates', ['templates' => $templates]);
    }

    public function dueReminder(): void
    {
        Auth::requireAuth();
        $recipients = $this->dueFeeRecipients();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->validate(['message' => 'required|max:1000']);
            if (empty($recipients)) {
                Session::getInstance()->flash('error', 'No students with outstanding dues to notify.');
                $this->back();
                return;
            }

            $campaignId = $this->db->insert('sms_campaigns', [
                'name'           => 'Due Fee Reminder ' . date('Y-m-d'),
                'audience_type'  => 'due_reminder',
                'message'        => $data['message'],
                'status'         => 'draft',
                'created_by'     => Auth::id(),
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            foreach ($recipients as $r) {
                $this->db->insert('sms_campaign_recipients', [
                    'sms_campaign_id' => $campaignId,
                    'phone'           => $r['phone'],
                    'user_type'       => 'student',
                    'user_id'         => $r['student_id'],
                    'status'          => 'queued',
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            }

            Session::getInstance()->flash('success', 'Due reminder campaign queued for ' . count($recipients) . ' recipients.');
            $this->redirect('/dashboard/sms');
            return;
        }

        $totalDue = array_sum(array_column($recipients, 'due'));
        $defaultMessage = 'Dear parent, your child has an outstanding fee balance of {{amount}}. Please clear the dues at your earliest convenience. - School Administration';

        $this->view('dashboard.sms.due_reminder', [
            'recipients'    => array_slice($recipients, 0, 50),
            'recipientCount'=> count($recipients),
            'totalDue'      => (float) $totalDue,
            'defaultMessage'=> $defaultMessage,
        ]);
    }

    private function resolveRecipients(array $data): array
    {
        $audience = $data['audience_type'] ?? 'all_users';
        $recipients = [];

        switch ($audience) {
            case 'students_class':
                $where = "s.phone_1 IS NOT NULL AND s.phone_1 != ''";
                $params = [];
                if (!empty($data['school_class_id'])) {
                    $where .= ' AND s.class_id = ?';
                    $params[] = $data['school_class_id'];
                }
                $rows = $this->db->fetchAll(
                    "SELECT s.id as student_id, u.name, COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) as phone
                     FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE {$where} LIMIT 200",
                    $params
                );
                foreach ($rows as $r) {
                    if (!empty($r['phone'])) {
                        $recipients[] = ['student_id' => $r['student_id'], 'name' => $r['name'] ?? 'Student', 'phone' => $r['phone'], 'user_type' => 'student'];
                    }
                }
                break;

            case 'students_section':
                $where = "s.phone_1 IS NOT NULL AND s.phone_1 != ''";
                $params = [];
                if (!empty($data['section_id'])) {
                    $where .= ' AND s.section_id = ?';
                    $params[] = $data['section_id'];
                }
                $rows = $this->db->fetchAll(
                    "SELECT s.id as student_id, u.name, COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) as phone
                     FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE {$where} LIMIT 200",
                    $params
                );
                foreach ($rows as $r) {
                    if (!empty($r['phone'])) {
                        $recipients[] = ['student_id' => $r['student_id'], 'name' => $r['name'] ?? 'Student', 'phone' => $r['phone'], 'user_type' => 'student'];
                    }
                }
                break;

            case 'staff_role':
                $roleName = $data['role_name'] ?? '';
                $where = "u.phone IS NOT NULL AND u.phone != ''";
                $params = [];
                if ($roleName !== '') {
                    $where .= ' AND r.name = ?';
                    $params[] = $roleName;
                }
                $rows = $this->db->fetchAll(
                    "SELECT u.id as user_id, u.name, u.phone FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE {$where} LIMIT 200",
                    $params
                );
                foreach ($rows as $r) {
                    $recipients[] = ['user_id' => $r['user_id'], 'name' => $r['name'], 'phone' => $r['phone'], 'user_type' => 'staff'];
                }
                break;

            case 'students_individual':
                $ids = $_POST['user_ids'] ?? [];
                if (empty($ids)) {
                    break;
                }
                $in = implode(',', array_map('intval', $ids));
                $rows = $this->db->fetchAll(
                    "SELECT s.id as student_id, u.name, COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) as phone
                     FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.user_id IN ({$in}) LIMIT 200"
                );
                foreach ($rows as $r) {
                    if (!empty($r['phone'])) {
                        $recipients[] = ['student_id' => $r['student_id'], 'name' => $r['name'] ?? 'Student', 'phone' => $r['phone'], 'user_type' => 'student'];
                    }
                }
                break;

            case 'staff_individual':
                $ids = $_POST['user_ids'] ?? [];
                if (empty($ids)) {
                    break;
                }
                $in = implode(',', array_map('intval', $ids));
                $rows = $this->db->fetchAll(
                    "SELECT id as user_id, name, phone FROM users WHERE id IN ({$in}) AND phone IS NOT NULL AND phone != '' LIMIT 200"
                );
                foreach ($rows as $r) {
                    $recipients[] = ['user_id' => $r['user_id'], 'name' => $r['name'], 'phone' => $r['phone'], 'user_type' => 'staff'];
                }
                break;

            case 'all_users':
            default:
                $rows = $this->db->fetchAll(
                    "SELECT s.id as student_id, u.name, COALESCE(NULLIF(s.phone_1, ''), NULLIF(s.father_phone, ''), NULLIF(s.mother_phone, '')) as phone
                     FROM students s LEFT JOIN users u ON s.user_id = u.id
                     WHERE s.phone_1 IS NOT NULL AND s.phone_1 != '' LIMIT 200"
                );
                foreach ($rows as $r) {
                    if (!empty($r['phone'])) {
                        $recipients[] = ['student_id' => $r['student_id'], 'name' => $r['name'] ?? 'Student', 'phone' => $r['phone'], 'user_type' => 'student'];
                    }
                }
                $staff = $this->db->fetchAll(
                    "SELECT id as user_id, name, phone FROM users WHERE phone IS NOT NULL AND phone != '' AND role_id IN (SELECT id FROM roles WHERE name IN ('teacher','staff','admin')) LIMIT 200"
                );
                foreach ($staff as $r) {
                    $recipients[] = ['user_id' => $r['user_id'], 'name' => $r['name'], 'phone' => $r['phone'], 'user_type' => 'staff'];
                }
                break;
        }

        // Dedupe by phone.
        $seen = [];
        $out = [];
        foreach ($recipients as $r) {
            if (!isset($seen[$r['phone']])) {
                $seen[$r['phone']] = true;
                $out[] = $r;
            }
        }
        return $out;
    }

    private function dueFeeRecipients(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT fp.student_id, s.phone_1, s.father_phone, s.mother_phone, u.name,
                    SUM(fp.balance) as due
             FROM fee_payments fp
             LEFT JOIN students s ON fp.student_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             WHERE fp.balance > 0 AND fp.status NOT IN ('paid', 'cancelled', 'refunded')
             GROUP BY fp.student_id, s.phone_1, s.father_phone, s.mother_phone, u.name"
        );

        $out = [];
        foreach ($rows as $r) {
            $phone = $r['phone_1'] ?: ($r['father_phone'] ?: $r['mother_phone']);
            if (empty($phone)) {
                continue;
            }
            $out[] = [
                'student_id' => $r['student_id'],
                'name'       => $r['name'] ?? ('Student #' . $r['student_id']),
                'phone'      => $phone,
                'due'        => (float) $r['due'],
            ];
        }
        return $out;
    }
}