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

// Include admission routes (includes both public and protected routes)
require __DIR__.'/admissions.php';

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles');
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Exam Module Routes
    Route::prefix('exams')->group(function () {
        // List all exams (with filters)
        Route::get('/', [ExamController::class, 'index']);
        
        // Create new exam (admin/teacher)
        Route::post('/', [ExamController::class, 'store'])
            ->middleware('permission:create_exams');
        
        // Get single exam details
        Route::get('/{exam}', [ExamController::class, 'show']);
        
        // Update exam (admin/teacher)
        Route::put('/{exam}', [ExamController::class, 'update'])
            ->middleware('permission:edit_exams');
        
        // Delete exam (admin)
        Route::delete('/{exam}', [ExamController::class, 'destroy'])
            ->middleware('permission:delete_exams');
        
        // Publish exam (admin/teacher)
        Route::post('/{exam}/publish', [ExamController::class, 'publish'])
            ->middleware('permission:publish_exams');
        
        // Unpublish exam (admin/teacher)
        Route::post('/{exam}/unpublish', [ExamController::class, 'unpublish'])
            ->middleware('permission:publish_exams');
        
        // Get exam results
        Route::get('/{exam}/results', [ExamController::class, 'results']);
        
        // Submit exam result (teacher)
        Route::post('/{exam}/results', [ExamController::class, 'submitResult'])
            ->middleware('permission:submit_exam_results');
        
        // Update exam result (teacher)
        Route::put('/{exam}/results/{result}', [ExamController::class, 'updateResult'])
            ->middleware('permission:edit_exam_results');
    });
});
