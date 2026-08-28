<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\QuickActionController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\FeePaymentController;
use App\Http\Controllers\Api\WebsiteContentController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\AcademicController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\CareerController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Admin\WebsiteSettingController as AdminWebsiteSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', [ApiController::class, 'index']);

    // Public academic & content routes
    Route::prefix('academics')->group(function () {
        Route::get('/curriculum', [AcademicController::class, 'getCurriculum']);
        Route::get('/programs', [AcademicController::class, 'getPrograms']);
        Route::get('/faculty', [AcademicController::class, 'getFaculty']);
        Route::get('/results/filters', [ResultController::class, 'filters']);
        Route::get('/results/lookup', [ResultController::class, 'lookup']);
    });

    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::get('/categories', [NewsController::class, 'categories']);
        Route::get('/upcoming-events', [NewsController::class, 'upcomingEvents']);
        Route::get('/{id}', [NewsController::class, 'show']);
    });

    Route::get('/website-content/{page}', [WebsiteContentController::class, 'getPageContent']);
    Route::get('/website-content/pages', [WebsiteContentController::class, 'getActivePages']);

    Route::prefix('website/gallery')->group(function () {
        Route::get('/', [GalleryController::class, 'index']);
        Route::get('/categories', [GalleryController::class, 'categories']);
    });

    Route::prefix('careers')->group(function () {
        Route::get('/', [CareerController::class, 'index']);
        Route::get('/{id}', [CareerController::class, 'show']);
        Route::post('/apply', [CareerController::class, 'apply'])
            ->middleware('throttle:'.config('api.rate_limits.public', 60).',1');
    });

    // Public legal & info pages
    Route::get('/terms', [LegalController::class, 'getTerms']);
    Route::get('/privacy', [LegalController::class, 'getPrivacy']);
    Route::get('/sitemap', [LegalController::class, 'getSitemap']);
    Route::get('/home', [LegalController::class, 'getHome']);

    // Public events
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    // Public contact - handled via web routes

    // Authentication
    Route::prefix('auth')->middleware('throttle:'.config('api.rate_limits.auth', 10).',1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    });

    // Website settings (public)
    Route::get('/website-settings', [AdminWebsiteSettingController::class, 'publicSettings']);

    // Teacher portal
    Route::middleware('auth:sanctum')->prefix('teacher')->group(function () {
        Route::get('/classes', [TeacherController::class, 'getTeacherClasses']);
        Route::get('/classes/{classId}/students', [TeacherController::class, 'getClassStudents']);
        Route::get('/classes/{classId}/grades', [TeacherController::class, 'getClassGrades']);
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());
        Route::get('/me', [AuthController::class, 'me']);

        // Search
        Route::prefix('search')->group(function () {
            Route::get('/', [SearchController::class, 'search']);
            Route::get('/{resource}', [SearchController::class, 'searchResource']);
        });

        // Fee management
        Route::prefix('fees')->group(function () {
            Route::get('/', [FeeController::class, 'index']);
            Route::post('/', [FeeController::class, 'store']);
            Route::get('/types', [FeeController::class, 'getFeeTypes']);
            Route::get('/statistics', [FeeController::class, 'getStatistics']);
            Route::get('/{fee}', [FeeController::class, 'show']);
            Route::put('/{fee}', [FeeController::class, 'update']);
            Route::delete('/{fee}', [FeeController::class, 'destroy']);
            Route::get('/{fee}/payments', [FeePaymentController::class, 'index']);
            Route::post('/{fee}/payments', [FeePaymentController::class, 'store']);
        });

        // Fee payments
        Route::prefix('fees/payments')->group(function () {
            Route::get('/statuses', [FeePaymentController::class, 'getStatuses']);
            Route::get('/methods', [FeePaymentController::class, 'getPaymentMethods']);
            Route::get('/{payment}', [FeePaymentController::class, 'show']);
            Route::put('/{payment}', [FeePaymentController::class, 'update']);
            Route::post('/{payment}/approve', [FeePaymentController::class, 'approve']);
            Route::post('/{payment}/cancel', [FeePaymentController::class, 'cancel']);
        });

        // Events (admin only)
        Route::prefix('events')->middleware('role:admin')->group(function () {
            Route::post('/', [EventController::class, 'store']);
            Route::put('/{event}', [EventController::class, 'update']);
            Route::delete('/{event}', [EventController::class, 'destroy']);
        });

        // Admin routes
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            // Analytics & Activity
            Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
            Route::get('/activity', [ActivityController::class, 'index']);
            Route::post('/quick-actions', [QuickActionController::class, 'handle']);

            // CMS Management
            Route::prefix('cms')->group(function () {
                // Pages
                Route::get('/pages', [CmsController::class, 'pages']);
                Route::post('/pages', [CmsController::class, 'storePage']);
                Route::get('/pages/{page}', [CmsController::class, 'showPage']);
                Route::put('/pages/{page}', [CmsController::class, 'updatePage']);
                Route::delete('/pages/{page}', [CmsController::class, 'destroyPage']);

                // Media
                Route::get('/media', [CmsController::class, 'media']);
                Route::post('/media', [CmsController::class, 'uploadMedia']);
                Route::delete('/media/{media}', [CmsController::class, 'destroyMedia']);

                // Menus
                Route::get('/menus', [CmsController::class, 'menus']);
                Route::put('/menus', [CmsController::class, 'updateMenus']);

                // Settings
                Route::get('/settings', [CmsController::class, 'settings']);
                Route::put('/settings', [CmsController::class, 'updateSettings']);

                // Header & Footer
                Route::get('/header', [CmsController::class, 'header']);
                Route::put('/header', [CmsController::class, 'updateHeader']);
                Route::get('/footer', [CmsController::class, 'footer']);
                Route::put('/footer', [CmsController::class, 'updateFooter']);

                // Content Blocks
                Route::get('/blocks', [CmsController::class, 'contentBlocks']);
                Route::post('/blocks', [CmsController::class, 'storeContentBlock']);
                Route::get('/blocks/{block}', [CmsController::class, 'showContentBlock']);
                Route::put('/blocks/{block}', [CmsController::class, 'updateContentBlock']);
                Route::delete('/blocks/{block}', [CmsController::class, 'destroyContentBlock']);
            });

            // Website Content Management
            Route::put('/website-content/{page}', [WebsiteContentController::class, 'updatePageContent']);
            Route::post('/website-content/{page}/upload-image', [WebsiteContentController::class, 'uploadImage']);

            // Widgets
            Route::prefix('widgets')->group(function () {
                Route::get('/', [DashboardController::class, 'getWidgetConfig']);
                Route::post('/', [DashboardController::class, 'saveWidgetConfig']);
                Route::post('/reset', [DashboardController::class, 'resetWidgetConfig']);
            });

            // Website Settings (admin)
            Route::get('/website-settings', [AdminWebsiteSettingController::class, 'index']);
            Route::post('/website-settings', [AdminWebsiteSettingController::class, 'update']);
        });
    });
});

// Public refund webhooks (outside v1 — gateways post here directly)
Route::post('/webhooks/{gateway}/refund', [\App\Http\Controllers\RefundController::class, 'webhook']);
