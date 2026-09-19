<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;

class EmailTemplateController extends Controller
{
    private const KEYS = [
        'welcome'          => 'Welcome email',
        'license_issued'   => 'License issued',
        'payment_received' => 'Payment received',
        'license_expiring' => 'License expiring soon',
        'package_available' => 'Package available',
        'contact_message'  => 'Contact form message',
    ];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $db = Database::getInstance();
        $existing = $db->fetchAll("SELECT * FROM email_templates ORDER BY tkey ASC");
        $map = [];
        foreach ($existing as $row) {
            $map[(string) $row['tkey']] = $row;
        }

        $now = date('Y-m-d H:i:s');
        $templates = [];
        foreach (self::KEYS as $key => $label) {
            if (!isset($map[$key])) {
                $db->insert('email_templates', [
                    'tkey'       => $key,
                    'subject'    => '',
                    'body'       => '',
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $map[$key] = [
                    'id'        => (int) $db->getConnection()->lastInsertId(),
                    'tkey'      => $key,
                    'subject'   => '',
                    'body'      => '',
                    'is_active' => 1,
                ];
            }
            $templates[] = ['key' => $key, 'label' => $label, 'row' => $map[$key]];
        }

        $this->view('admin.email-templates', ['admin' => Auth::user(), 'templates' => $templates]);
    }

    public function update(int $id): void
    {
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = (string) ($_POST['body'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;

        Database::getInstance()->update('email_templates', [
            'subject'    => $subject,
            'body'       => $body,
            'is_active'  => $active,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('email_template.updated', 'admin', (int) Auth::id(), ['template_id' => $id]);

        $this->withSuccess('Email template saved. Leave subject/body empty to use the built-in default.');
        $this->redirect('/admin/email-templates');
    }
}