<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\NotificationService  $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:api');
        $this->notificationService = $notificationService;
    }

    /**
     * Get the authenticated user's notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $offset = $request->input('offset', 0);
        $unreadOnly = $request->boolean('unread_only', false);
        
        $query = Auth::user()->notifications();
        
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }
        
        $notifications = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
            
        $unreadCount = Auth::user()->unreadNotifications()->count();
        
        return response()->json([
            'data' => $notifications,
            'meta' => [
                'total' => $notifications->count(),
                'unread_count' => $unreadCount,
                'has_more' => $notifications->count() >= $limit,
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(string $id)
    {
        $result = $this->notificationService->markAsRead(
            $id,
            Auth::id()
        );
        
        if ($result) {
            return response()->json([
                'message' => 'Notification marked as read.',
                'unread_count' => $this->notificationService->getUnreadCount(Auth::id()),
            ]);
        }
        
        return response()->json([
            'message' => 'Notification not found or already read.',
        ], 404);
    }

    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(Auth::id());
        
        return response()->json([
            'message' => "{$count} notifications marked as read.",
            'unread_count' => 0,
        ]);
    }

    /**
     * Get the authenticated user's notification preferences.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreferences()
    {
        $preferences = NotificationPreference::getUserPreferences(Auth::id());
        
        return response()->json([
            'data' => $preferences,
        ]);
    }

    /**
     * Update the authenticated user's notification preferences.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'array',
            'preferences.*.email' => 'sometimes|boolean',
            'preferences.*.sms' => 'sometimes|boolean',
            'preferences.*.push' => 'sometimes|boolean',
            'preferences.*.in_app' => 'sometimes|boolean',
        ]);
        
        $result = NotificationPreference::setUserPreferences(
            Auth::id(),
            $validated['preferences']
        );
        
        if ($result) {
            return response()->json([
                'message' => 'Notification preferences updated successfully.',
                'data' => NotificationPreference::getUserPreferences(Auth::id()),
            ]);
        }
        
        return response()->json([
            'message' => 'Failed to update notification preferences.',
        ], 500);
    }

    /**
     * Get the authenticated user's unread notifications count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(Auth::id());
        
        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->find($id);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }
        
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted successfully.',
            'unread_count' => $this->notificationService->getUnreadCount(Auth::id()),
        ]);
    }

    /**
     * Clear all notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearAll()
    {
        $count = Auth::user()->notifications()->delete();
        
        return response()->json([
            'message' => "{$count} notifications cleared.",
            'unread_count' => 0,
        ]);
    }
}
