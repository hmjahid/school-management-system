<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class CommunicationController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $stats = [
            'messages'       => (int) $db->count('messages'),
            'notifications'  => (int) $db->count('notification_logs'),
            'sms_campaigns'  => (int) $db->count('sms_campaigns'),
            'announcements'  => (int) $db->count('announcements'),
        ];

        $this->view('dashboard.communications.index', ['stats' => $stats]);
    }
}
