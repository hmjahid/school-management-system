<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $messages = Message::where('receiver_id', $user->id)
            ->with('sender')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $unreadCount = Message::where('receiver_id', $user->id)->unread()->count();

        return view('messages.inbox', compact('messages', 'unreadCount'));
    }

    public function sent(Request $request): View
    {
        $messages = Message::where('sender_id', $request->user()->id)
            ->with('receiver')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('messages.sent', compact('messages'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $roles = Role::orderBy('name')->get();
        $allUsers = User::where('id', '!=', $user->id)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role_names' => $u->roles->pluck('name')->join(', '),
            ]);

        $replyTo = null;
        if ($request->filled('reply_to')) {
            $replyTo = Message::where('id', $request->reply_to)
                ->where('receiver_id', $user->id)
                ->first();
        }

        return view('messages.create', compact('roles', 'allUsers', 'replyTo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $validated['sender_id'] = $request->user()->id;

        Message::create($validated);

        return redirect()->route('messages.sent')->with('status', __('Message sent successfully.'));
    }

    public function show(Request $request, int $id): View
    {
        $message = Message::findOrFail($id);

        if ($message->receiver_id === $request->user()->id && !$message->read_at) {
            $message->markAsRead();
        }

        $message->load(['sender', 'receiver']);

        return view('messages.show', ['message' => $message]);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $message = Message::findOrFail($id);
        $user = $request->user();

        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            abort(403);
        }

        $message->delete();

        $redirect = $request->user()->hasRole(['admin', 'teacher', 'staff'])
            ? route('messages.index')
            : route('messages.index');

        return redirect()->route('messages.index')->with('status', __('Message deleted.'));
    }
}
