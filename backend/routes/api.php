<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', [ApiController::class, 'index']);

// Admin routes
Route::middleware(['auth:api'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
});

// Academic routes
Route::prefix('academics')->group(function () {
    Route::get('/curriculum', [\App\Http\Controllers\Api\AcademicController::class, 'getCurriculum']);
    Route::get('/programs', [\App\Http\Controllers\Api\AcademicController::class, 'getPrograms']);
    Route::get('/faculty', [\App\Http\Controllers\Api\AcademicController::class, 'getFaculty']);
});

// News and Events routes
Route::prefix('news')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\NewsController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\NewsController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\NewsController::class, 'categories']);
    Route::get('/upcoming-events', [\App\Http\Controllers\Api\NewsController::class, 'upcomingEvents']);
});

// Website content routes
Route::get('/website-content/{page}', [\App\Http\Controllers\Api\WebsiteContentController::class, 'getPageContent']);

// Gallery routes
Route::prefix('website/gallery')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\GalleryController::class, 'index']);
    Route::get('/categories', [\App\Http\Controllers\Api\GalleryController::class, 'categories']);
});

// Career routes
Route::prefix('careers')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CareerController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\CareerController::class, 'show']);
    Route::post('/apply', [\App\Http\Controllers\Api\CareerController::class, 'apply']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh-token', [\App\Http\Controllers\Auth\AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/me', [\App\Http\Controllers\Auth\AuthController::class, 'me']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        // Dashboard routes
        Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        
        // Widget configuration routes
        Route::prefix('widgets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'getWidgetConfig']);
            Route::post('/', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'saveWidgetConfig']);
            Route::post('/reset', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'resetWidgetConfig']);
        });
    });
});
