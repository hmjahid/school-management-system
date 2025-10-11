<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register payment related API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::prefix('payments')->group(function () {
    // Get list of available payment gateways
    Route::get('/gateways', [PaymentController::class, 'gateways'])->name('payments.gateways');
    
    // Initiate a new payment
    Route::post('/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
    
    // Payment callback from gateway (public endpoint)
    Route::post('/callback/{gateway}', [PaymentController::class, 'callback'])
        ->name('payments.callback');
        
    // Payment webhook from gateway (public endpoint)
    Route::post('/webhook/{gateway}', [PaymentController::class, 'webhook'])
        ->name('payments.webhook');
        
    // Check payment status (public endpoint)
    Route::get('/status/{payment}', [PaymentController::class, 'status'])
        ->name('payments.status');
});

// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('payments')->group(function () {
        // List all payments (with filters)
        Route::get('/', [PaymentController::class, 'index'])
            ->name('payments.index')
            ->middleware('can:viewAny,App\Models\Payment');
            
        // Get payment details
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('payments.show')
            ->middleware('can:view,payment');
            
        // Update payment status (admin only)
        Route::put('/{payment}/status', [PaymentController::class, 'updateStatus'])
            ->name('payments.update.status')
            ->middleware('can:updateStatus,payment');
            
        // Record an offline payment
        Route::post('/record-offline', [PaymentController::class, 'recordOfflinePayment'])
            ->name('payments.record-offline')
            ->middleware('can:recordOffline,App\Models\Payment');
            
        // Refund a payment
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])
            ->name('payments.refund')
            ->middleware('can:refund,payment');
            
        // Export payments
        Route::get('/export', [PaymentController::class, 'export'])
            ->name('payments.export')
            ->middleware('can:export,App\Models\Payment');
    });
    
    // Payment gateway management (admin only)
    Route::apiResource('payment-gateways', \App\Http\Controllers\PaymentGatewayController::class)
        ->except(['create', 'edit'])
        ->middleware('can:manageGateways,App\Models\Payment');
});
