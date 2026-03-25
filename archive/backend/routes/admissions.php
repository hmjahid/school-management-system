<?php

use App\Http\Controllers\AdmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admission API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admission related API routes. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (for form submission)
Route::prefix('admissions')->group(function () {
    // Submit a new admission application (public endpoint)
    Route::post('/', [AdmissionController::class, 'store'])
        ->middleware('throttle:10,1'); // Rate limiting to prevent abuse
    
    // Check admission status (public endpoint with token)
    Route::get('/status/{admission:application_number}', [AdmissionController::class, 'status'])
        ->name('admissions.status');
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // Admission management
    Route::apiResource('admissions', AdmissionController::class)->except(['store']);
    
    // Admission actions
    Route::prefix('admissions/{admission}')->group(function () {
        // Submit for review
        Route::post('/submit', [AdmissionController::class, 'submit'])
            ->name('admissions.submit');
            
        // Approve admission
        Route::post('/approve', [AdmissionController::class, 'approve'])
            ->name('admissions.approve');
            
        // Reject admission
        Route::post('/reject', [AdmissionController::class, 'reject'])
            ->name('admissions.reject');
            
        // Enroll student
        Route::post('/enroll', [AdmissionController::class, 'enroll'])
            ->name('admissions.enroll');
            
        // Document management
        Route::prefix('documents')->group(function () {
            // Upload document
            Route::post('/', [AdmissionController::class, 'uploadDocument'])
                ->name('admissions.documents.upload');
                
            // Delete document
            Route::delete('/{document}', [AdmissionController::class, 'deleteDocument'])
                ->name('admissions.documents.delete');
                
            // View document
            Route::get('/{document}', [AdmissionController::class, 'viewDocument'])
                ->name('admissions.documents.view');
        });
    });
    
    // Admission filters and options
    Route::get('admission-filters', [AdmissionController::class, 'filterOptions'])
        ->name('admissions.filters');
        
    // Export admissions
    Route::get('export/admissions', [AdmissionController::class, 'export'])
        ->name('admissions.export');
        
    // Import admissions
    Route::post('import/admissions', [AdmissionController::class, 'import'])
        ->name('admissions.import');
});
