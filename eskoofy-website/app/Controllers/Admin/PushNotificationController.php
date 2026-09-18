<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\Mailer;

class PushNotificationController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT pn.*, (SELECT COUNT(*) FROM push_notification_reads r WHERE r.notification_id = pn.id) AS read_count
             FROM push_notifications pn
             ORDER BY pn.id DESC LIMIT 50"
        );
        $this->view('admin.push-notifications', ['admin' => Auth::user(), 'notifications' => $rows]);
    }

    public function store(): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $link = trim((string) ($_POST['link'] ?? ''));
        $email = isset($_POST['email_too']) ? true : false;

        if ($title === '') {
            $this->withError('A notification title is required.');
            $this->redirect('/admin/push-notifications');
        }

        Database::getInstance()->insert('push_notifications', [
            'title'      => $title,
            'message'    => $message,
            'link'       => $link !== '' ? $link : null,
            'created_by' => (int) Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($email) {
            $customers = Database::getInstance()->fetchAll(
                "SELECT id, name, email FROM customers WHERE deleted_at IS NULL AND email <> ''"
            );
            $sent = 0;
            foreach ($customers as $c) {
                if (Mailer::sendView((string) $c['email'], $title, 'welcome', [
                    'name'     => $c['name'] ?: 'there',
                    'document' => $message,
                ])) {
                    $sent++;
                }
            }
        } else {
            $sent = 0;
        }

        ActivityLog::log('push_notification.sent', 'admin', (int) Auth::id(), [
            'title' => $title,
            'emails_sent' => $sent,
        ]);

        $this->withSuccess('Push notification sent to all customers' . ($sent ? " (+ {$sent} emails)" : '') . '.');
        $this->redirect('/admin/push-notifications');
    }

    public function delete(int $id): void
    {
        Database::getInstance()->delete('push_notifications', 'id = ?', [$id]);
        Database::getInstance()->delete('push_notification_reads', 'notification_id = ?', [$id]);
        $this->withSuccess('Notification deleted.');
        $this->redirect('/admin/push-notifications');
    }
}