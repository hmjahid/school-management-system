<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Schema;
use App\Core\Session;
use App\Core\Support\Collection;
use App\Core\Support\LengthAwarePaginator;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;

class MessageController extends Controller
{
    private DatabaseInterface $db;

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

        $messages = new LengthAwarePaginator([], 0, $perPage, $page);
        $unreadCount = 0;
        try {
            if (Schema::hasTable('messages')) {
                $total = (int) Message::query()->where('receiver_id', $userId)->count();
                $rows = Message::query()
                    ->where('receiver_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit($perPage)
                    ->offset(($page - 1) * $perPage)
                    ->get();
                $messages = $this->paginateRows($rows, $total, $perPage, $page);
                $unreadCount = (int) Message::query()->where('receiver_id', $userId)->whereNull('read_at')->count();
            }
        } catch (\Throwable) {
            $messages = new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $data = array_merge($this->dashboardShellData(), [
            'messages'    => $messages,
            'unreadCount' => $unreadCount,
        ]);
        $this->view('messages.inbox', $data);
    }

    public function sent(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        $messages = new LengthAwarePaginator([], 0, $perPage, $page);
        try {
            if (Schema::hasTable('messages')) {
                $total = (int) Message::query()->where('sender_id', $userId)->count();
                $rows = Message::query()
                    ->where('sender_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit($perPage)
                    ->offset(($page - 1) * $perPage)
                    ->get();
                $messages = $this->paginateRows($rows, $total, $perPage, $page);
            }
        } catch (\Throwable) {
            $messages = new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $data = array_merge($this->dashboardShellData(), ['messages' => $messages]);
        $this->view('messages.sent', $data);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $roles = new Collection();
        $allUsers = new Collection();
        try {
            if (Schema::hasTable('roles')) {
                $roles = new Collection(Role::query()->orderBy('name')->get());
            }
            if (Schema::hasTable('users')) {
                $users = User::query()
                    ->where('id', '!=', $userId)
                    ->orderBy('name')
                    ->limit(500)
                    ->get();
                $allUsers = $users->map(fn ($u) => [
                    'id'         => (int) $u->id,
                    'name'       => (string) $u->name,
                    'role_names' => $this->userRoleNames($u),
                ]);
            }
        } catch (\Throwable) {
            $roles = new Collection();
            $allUsers = new Collection();
        }

        $replyTo = null;
        $replyId = (int) ($_GET['reply_to'] ?? 0);
        if ($replyId > 0) {
            try {
                $replyTo = Message::query()->where('id', $replyId)->where('receiver_id', $userId)->first();
            } catch (\Throwable) {
                $replyTo = null;
            }
        }

        $data = array_merge($this->dashboardShellData(), [
            'roles'    => $roles,
            'allUsers' => $allUsers->all(),
            'replyTo'  => $replyTo,
        ]);
        $this->view('messages.create', $data);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        $message = null;
        try {
            $message = Message::find((int) $id);
        } catch (\Throwable) {
            $message = null;
        }

        if (!$message) {
            Session::getInstance()->flash('error', __('Message not found.'));
            $this->redirect('/messages');
            return;
        }

        if ((int) $message->sender_id !== $userId && (int) $message->receiver_id !== $userId) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        if ((int) $message->receiver_id === $userId && !$message->read_at) {
            try {
                $message->update([
                    'is_read'  => 1,
                    'read_at'  => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                //
            }
        }

        // Force-load the sender/receiver relations for the show view.
        $message->sender;
        $message->receiver;

        $data = array_merge($this->dashboardShellData(), ['message' => $message]);
        $this->view('messages.show', $data);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'receiver_id' => 'required|numeric',
            'subject'     => 'required|max:255',
            'body'        => 'required|max:5000',
        ]);

        try {
            Message::create([
                'sender_id'   => Auth::id(),
                'receiver_id' => (int) $data['receiver_id'],
                'subject'     => $data['subject'],
                'body'        => $data['body'],
                'is_read'     => 0,
            ]);
        } catch (\Throwable) {
            Session::getInstance()->flash('error', __('Could not send message.'));
            $this->back();
            return;
        }

        Session::getInstance()->flash('success', __('Message sent successfully.'));
        $this->redirect('/messages/sent');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $userId = Auth::id();

        try {
            $message = Message::find((int) $id);
            if ($message
                && ((int) $message->sender_id === $userId || (int) $message->receiver_id === $userId)) {
                $message->delete();
            }
        } catch (\Throwable) {
            //
        }

        Session::getInstance()->flash('success', __('Message deleted.'));
        $this->redirect('/messages');
    }

    /**
     * Best-effort role names for the recipient dropdown (spatie model_has_roles
     * then the legacy user.role column).
     */
    private function userRoleNames(User $u): string
    {
        $names = [];
        $role = $u->attributes['role'] ?? null;
        if ($role) {
            $names[] = (string) $role;
        }
        try {
            $rows = $this->db->fetchAll(
                "SELECT r.name FROM roles r
                 JOIN model_has_roles mr ON mr.role_id = r.id
                 WHERE mr.model_id = ? AND mr.model_type = 'App\\\\Models\\\\User'",
                [(int) $u->id]
            );
            foreach ($rows as $row) {
                if (!empty($row['name'])) {
                    $names[] = (string) $row['name'];
                }
            }
        } catch (\Throwable) {
            //
        }
        return implode(', ', array_unique($names));
    }
}