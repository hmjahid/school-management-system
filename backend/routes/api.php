<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Website content routes - Public
Route::prefix('website')->group(function () {
    // About page content
    Route::get('/about', [\App\Http\Controllers\Api\Website\AboutContentController::class, 'index']);
    Route::get('/contact', [\App\Http\Controllers\Api\Website\AboutContentController::class, 'contact']);
    
    // Legal pages
    Route::get('/legal/terms', [\App\Http\Controllers\Api\LegalController::class, 'getTerms']);
    Route::get('/legal/privacy', [\App\Http\Controllers\Api\LegalController::class, 'getPrivacy']);
    Route::get('/sitemap', [\App\Http\Controllers\Api\LegalController::class, 'getSitemap']);
    Route::get('/home', [\App\Http\Controllers\Api\LegalController::class, 'getHome']);
    
    // Public website settings
    Route::get('/settings', [\App\Http\Controllers\Admin\WebsiteSettingController::class, 'publicSettings']);
});

// Include admission routes (includes both public and protected routes)
require __DIR__.'/admissions.php';

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get current user
    Route::get('/me', [AuthController::class, 'me']);
    
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin-only routes
    Route::middleware(['admin'])->group(function () {
        // Admin website content management
        Route::prefix('admin/website')->group(function () {
            // Update about content
            Route::put('/about', [\App\Http\Controllers\Api\Website\AboutContentController::class, 'update']);
            
            // Upload logo and favicon
            Route::post('/logo', [\App\Http\Controllers\Api\Website\AboutContentController::class, 'uploadLogo']);
            Route::post('/favicon', [\App\Http\Controllers\Api\Website\AboutContentController::class, 'uploadFavicon']);
            
            // Website settings management
            Route::prefix('settings')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\WebsiteSettingController::class, 'index']);
                Route::put('/', [\App\Http\Controllers\Admin\WebsiteSettingController::class, 'update']);
            });
        });

        // Exam Module Routes (admin only)
        Route::prefix('exams')->group(function () {
            // List all exams (with filters)
            Route::get('/', [ExamController::class, 'index']);
            
            // Create new exam
            Route::post('/', [ExamController::class, 'store']);
            
            // Get single exam details
            Route::get('/{exam}', [ExamController::class, 'show']);
            
            // Update exam
            Route::put('/{exam}', [ExamController::class, 'update']);
            
            // Delete exam
            Route::delete('/{exam}', [ExamController::class, 'destroy']);
            
            // Publish exam
            Route::post('/{exam}/publish', [ExamController::class, 'publish']);
            
            // Unpublish exam
            Route::post('/{exam}/unpublish', [ExamController::class, 'unpublish']);
            
            // Get exam results
            Route::get('/{exam}/results', [ExamController::class, 'results']);
            
            // Submit exam result
            Route::post('/{exam}/results', [ExamController::class, 'submitResult']);
            
            // Update exam result
            Route::put('/{exam}/results/{result}', [ExamController::class, 'updateResult']);
        });
    });

    // Teacher routes
    Route::middleware(['permission:manage_exams'])->group(function () {
        // Teacher-specific exam routes can be added here
    });
});
