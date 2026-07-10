<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (versioned)
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1 by the v1 group below.
| Laravel adds the /api prefix automatically for this file.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/', [ApiController::class, 'index']);

    // Public academic & content routes
    Route::prefix('academics')->group(function () {
        Route::get('/curriculum', [\App\Http\Controllers\Api\AcademicController::class, 'getCurriculum']);
        Route::get('/programs', [\App\Http\Controllers\Api\AcademicController::class, 'getPrograms']);
        Route::get('/faculty', [\App\Http\Controllers\Api\AcademicController::class, 'getFaculty']);
    });

    Route::prefix('news')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NewsController::class, 'index']);
        Route::get('/categories', [\App\Http\Controllers\Api\NewsController::class, 'categories']);
        Route::get('/upcoming-events', [\App\Http\Controllers\Api\NewsController::class, 'upcomingEvents']);
        Route::get('/{id}', [\App\Http\Controllers\Api\NewsController::class, 'show']);
    });

    Route::get('/website-content/{page}', [\App\Http\Controllers\Api\WebsiteContentController::class, 'getPageContent']);

    Route::prefix('website/gallery')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\GalleryController::class, 'index']);
        Route::get('/categories', [\App\Http\Controllers\Api\GalleryController::class, 'categories']);
    });

    Route::prefix('careers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CareerController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\CareerController::class, 'show']);
        Route::post('/apply', [\App\Http\Controllers\Api\CareerController::class, 'apply'])
            ->middleware('throttle:'.config('api.rate_limits.public', 60).',1');
    });

    // Authentication
    Route::prefix('auth')->middleware('throttle:'.config('api.rate_limits.auth', 10).',1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    });

    // Teacher portal
    Route::middleware('auth:sanctum')->prefix('teacher')->group(function () {
        Route::get('/classes', [\App\Http\Controllers\Api\TeacherController::class, 'getTeacherClasses']);
        Route::get('/classes/{classId}/students', [\App\Http\Controllers\Api\TeacherController::class, 'getClassStudents']);
        Route::get('/classes/{classId}/grades', [\App\Http\Controllers\Api\TeacherController::class, 'getClassGrades']);
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());
        Route::get('/me', [AuthController::class, 'me']);

        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

            Route::prefix('widgets')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'getWidgetConfig']);
                Route::post('/', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'saveWidgetConfig']);
                Route::post('/reset', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'resetWidgetConfig']);
            });
        });
    });
});
