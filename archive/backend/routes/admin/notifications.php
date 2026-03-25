<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\NotificationPreferenceController;

/*
|--------------------------------------------------------------------------
| Admin Notification Routes
|--------------------------------------------------------------------------
|
| These routes are for managing notifications in the admin panel.
| They are protected by the 'auth:api' and 'role:admin' middleware.
|
*/

Route::prefix('notifications')->group(function () {
    // Notification Templates
    Route::prefix('templates')->group(function () {
        // List all templates with pagination
        Route::get('/', [NotificationTemplateController::class, 'index']);
        
        // Create a new template
        Route::post('/', [NotificationTemplateController::class, 'store']);
        
        // Preview a template with sample data
        Route::post('/preview', [NotificationTemplateController::class, 'preview']);
        
        // Get available notification types
        Route::get('/types', [NotificationTemplateController::class, 'types']);
        
        // Get variables for a notification type
        Route::get('/types/{type}/variables', [NotificationTemplateController::class, 'variables']);
        
        // Get a single template
        Route::get('/{template}', [NotificationTemplateController::class, 'show']);
        
        // Update a template
        Route::put('/{template}', [NotificationTemplateController::class, 'update']);
        
        // Delete a template
        Route::delete('/{template}', [NotificationTemplateController::class, 'destroy']);
    });
    
    // User Notification Preferences (Admin)
    Route::prefix('preferences')->group(function () {
        // List all user preferences (paginated)
        Route::get('/', [NotificationPreferenceController::class, 'indexAll']);
        
        // Get preferences for a specific user
        Route::get('/user/{userId}', [NotificationPreferenceController::class, 'forUser']);
    });
});
