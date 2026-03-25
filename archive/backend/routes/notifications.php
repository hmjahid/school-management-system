<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
|
| Here is where you can register notification related routes for your application.
|
*/

// Group routes that require authentication
Route::middleware(['auth:api'])->group(function () {
    // Get all notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    
    // Get unread notifications count
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Mark a notification as read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->where('id', '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}');
    
    // Mark all notifications as read
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    
    // Delete a notification
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])
        ->where('id', '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}');
    
    // Clear all notifications
    Route::delete('/notifications', [NotificationController::class, 'clearAll']);
    
    // Get notification preferences
    Route::get('/notification-preferences', [NotificationController::class, 'getPreferences']);
    
    // Update notification preferences
    Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']);
});

// WebSocket/SSE endpoint for real-time notifications
Route::middleware(['auth:api'])->group(function () {
    Route::get('/notifications/stream', function () {
        // This will be handled by the broadcast/auth route in the BroadcastServiceProvider
        return response()->json(['message' => 'WebSocket/SSE connection established.']);
    });
});
