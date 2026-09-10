<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class MessageController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll("SELECT * FROM contact_messages ORDER BY id DESC");

        $this->view('admin.messages', ['admin' => Auth::user(), 'messages' => $rows]);
    }

    public function markRead(int $id): void
    {
        Database::getInstance()->update('contact_messages', [
            'read_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->withSuccess('Message marked as read.');
        $this->redirect('/admin/messages');
    }
}
