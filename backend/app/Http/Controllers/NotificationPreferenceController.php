<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    /**
     * Get the notification preferences for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get all notification types with their default preferences
        $defaultPreferences = config('notifications.types', []);
        
        // Get user's custom preferences
        $userPreferences = $user->notificationPreferences()
            ->get()
            ->keyBy('type')
            ->map(function ($pref) {
                return [
                    'channels' => $pref->channels,
                    'is_custom' => true,
                ];
            });
        
        // Merge default preferences with user's custom preferences
        $preferences = collect($defaultPreferences)->map(function ($channels, $type) use ($userPreferences) {
            return [
                'type' => $type,
                'channels' => $channels,
                'is_custom' => false,
                ...($userPreferences->get($type, [])),
            ];
        })->values();
        
        return response()->json([
            'data' => $preferences,
        ]);
    }

    /**
     * Update the specified notification preference.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $type)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'channels' => 'required|array',
            'channels.*' => 'in:database,mail,sms,push',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Check if the notification type exists in the config
        $defaultChannels = config("notifications.types.{$type}");
        
        if (is_null($defaultChannels)) {
            return response()->json([
                'message' => 'Invalid notification type',
            ], 404);
        }
        
        // Only allow enabling channels that are available in the default config
        $validatedChannels = $request->input('channels');
        $filteredChannels = array_intersect($validatedChannels, $defaultChannels);
        
        // Update or create the preference
        $preference = $user->notificationPreferences()->updateOrCreate(
            ['type' => $type],
            ['channels' => $filteredChannels]
        );
        
        return response()->json([
            'message' => 'Notification preference updated successfully',
            'data' => [
                'type' => $preference->type,
                'channels' => $preference->channels,
                'is_custom' => true,
            ],
        ]);
    }

    /**
     * Reset a notification preference to system defaults.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request, string $type)
    {
        $user = $request->user();
        
        // Check if the notification type exists in the config
        $defaultChannels = config("notifications.types.{$type}");
        
        if (is_null($defaultChannels)) {
            return response()->json([
                'message' => 'Invalid notification type',
            ], 404);
        }
        
        // Delete the user's custom preference
        $user->notificationPreferences()
            ->where('type', $type)
            ->delete();
        
        return response()->json([
            'message' => 'Notification preference reset to defaults',
            'data' => [
                'type' => $type,
                'channels' => $defaultChannels,
                'is_custom' => false,
            ],
        ]);
    }

    /**
     * Get the notification preferences for all users (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexAll(Request $request)
    {
        $this->authorize('viewAny', NotificationPreference::class);
        
        $preferences = NotificationPreference::with('user:id,name,email')
            ->get()
            ->groupBy('user_id');
            
        return response()->json([
            'data' => $preferences,
        ]);
    }
    
    /**
     * Get notification preferences for a specific user (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function forUser(Request $request, $userId)
    {
        $this->authorize('viewAny', NotificationPreference::class);
        
        $user = User::findOrFail($userId);
        $preferences = $user->notificationPreferences;
        
        return response()->json([
            'data' => $preferences,
        ]);
    }
}
