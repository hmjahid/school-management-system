<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest('created_at')->paginate(20);
        $unreadCount = $user->unreadNotifications()->count();

        return view('dashboard.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest('created_at')->limit(15)->get();

        $items = $notifications->map(function ($n) {
            $data = (array) $n->data;
            $type = $n->type ? class_basename($n->type) : 'Notification';
            return [
                'id' => $n->id,
                'type' => $type,
                'title' => $data['title'] ?? $type,
                'message' => $data['message'] ?? $data['body'] ?? '',
                'url' => $data['url'] ?? null,
                'unread' => $n->read_at === null,
                'created_at' => $n->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'items' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
            'csrf' => csrf_token(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'unread_count' => $request->user()->unreadNotifications()->count()]);
        }

        $url = (string) ($notification->data['url'] ?? route('notifications.index'));
        return redirect()->to($url);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }
}
