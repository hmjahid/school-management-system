<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    /**
     * Get the notification preferences for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $preferences = collect(NotificationPreference::getDefaultPreferences())
            ->map(fn ($channels, $type) => [
                'type' => $type,
                'channels' => $channels,
                'is_custom' => NotificationPreference::where('user_id', $user->id)
                    ->where('notification_type', $type)
                    ->exists(),
            ])
            ->values();

        return response()->json(['data' => $preferences]);
    }

    /**
     * Update the specified notification preference.
     */
    public function update(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        if (! NotificationPreference::isValidType($type)) {
            return response()->json(['message' => 'Invalid notification type'], 404);
        }

        $validator = Validator::make($request->all(), [
            'channels' => 'required|array',
            'channels.*' => 'in:'.implode(',', NotificationPreference::getAvailableChannels()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('channels') as $channel) {
            NotificationPreference::setUserPreference($user->id, $type, $channel, true);
        }
        foreach (NotificationPreference::getAvailableChannels() as $channel) {
            if (! in_array($channel, $request->input('channels'))) {
                NotificationPreference::setUserPreference($user->id, $type, $channel, false);
            }
        }

        return response()->json([
            'message' => 'Notification preference updated successfully',
            'data' => [
                'type' => $type,
                'channels' => NotificationPreference::getDefaultPreferenceForType($type),
                'is_custom' => true,
            ],
        ]);
    }

    /**
     * Reset a notification preference to system defaults.
     */
    public function reset(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        if (! NotificationPreference::isValidType($type)) {
            return response()->json(['message' => 'Invalid notification type'], 404);
        }

        NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->delete();

        return response()->json([
            'message' => 'Notification preference reset to defaults',
            'data' => [
                'type' => $type,
                'channels' => NotificationPreference::getDefaultPreferenceForType($type),
                'is_custom' => false,
            ],
        ]);
    }

    /**
     * Get the notification preferences for all users (admin only).
     */
    public function indexAll(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::with('user:id,name,email')
            ->get()
            ->groupBy('user_id');

        return response()->json(['data' => $preferences]);
    }

    /**
     * Get notification preferences for a specific user (admin only).
     */
    public function forUser(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $preferences = collect(NotificationPreference::getDefaultPreferences())
            ->map(fn ($channels, $type) => [
                'type' => $type,
                'channels' => $channels,
                'preference' => NotificationPreference::where('user_id', $user->id)
                    ->where('notification_type', $type)
                    ->first(['email', 'sms', 'push', 'in_app']),
            ])
            ->values();

        return response()->json(['data' => $preferences]);
    }
}