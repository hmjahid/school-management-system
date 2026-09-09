<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class MessageController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = ?", [$userId]
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT m.*, u.name as sender_name
             FROM messages m
             LEFT JOIN users u ON m.sender_id = u.id
             WHERE m.receiver_id = ?
             ORDER BY m.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$userId]
        );

        $users = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE id != ? AND deleted_at IS NULL ORDER BY name ASC LIMIT 500",
            [$userId]
        );

        $this->view('dashboard.messages.index', [
            'rows'     => $rows,
            'messages' => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'users'    => $users,
        ]);
    }

    public function sent(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM messages WHERE sender_id = ?", [$userId]
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT m.*, u.name as receiver_name
             FROM messages m
             LEFT JOIN users u ON m.receiver_id = u.id
             WHERE m.sender_id = ?
             ORDER BY m.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$userId]
        );

        $this->view('dashboard.messages.index', [
            'rows'    => $rows,
            'messages' => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'sent'    => true,
            'users'   => [],
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $message = $this->db->fetch(
            "SELECT m.*, su.name as sender_name, ru.name as receiver_name
             FROM messages m
             LEFT JOIN users su ON m.sender_id = su.id
             LEFT JOIN users ru ON m.receiver_id = ru.id
             WHERE m.id = ? LIMIT 1",
            [$id]
        );

        if (!$message) {
            Session::getInstance()->flash('error', 'Message not found.');
            $this->redirect('/dashboard/messages');
            return;
        }

        if ($message['receiver_id'] == Auth::id() && !$message['is_read']) {
            $this->db->update('messages', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        }

        $this->view('dashboard.messages.show', ['message' => $message]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'receiver_id' => 'required|numeric',
            'subject'     => 'required|max:255',
            'body'        => 'required|max:5000',
        ]);

        $this->db->insert('messages', [
            'sender_id'   => Auth::id(),
            'receiver_id' => $data['receiver_id'],
            'subject'     => $data['subject'],
            'body'        => $data['body'],
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Message sent.');
        $this->redirect('/dashboard/messages');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('messages', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Message deleted.');
        $this->redirect('/dashboard/messages');
    }
}
