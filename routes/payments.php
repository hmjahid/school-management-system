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

// Public routes (gateway-facing only)
Route::prefix('payments')->group(function () {
    // Get list of available payment gateways
    Route::get('/gateways', [PaymentController::class, 'gateways'])->name('payments.gateways');

    // Payment callback from gateway (public endpoint)
    Route::post('/callback/{gateway}', [PaymentController::class, 'callback'])
        ->name('payments.callback');

    // Payment webhook from gateway (public endpoint)
    Route::post('/webhook/{gateway}', [PaymentController::class, 'webhook'])
        ->name('payments.webhook');
});

// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('payments')->group(function () {
        // Initiate a new payment (authenticated only)
        Route::post('/initiate', [PaymentController::class, 'initiate'])
            ->name('payments.initiate')
            ->middleware('can:initiate,App\Models\Payment');

        // Check payment status (owner or permission)
        Route::get('/status/{payment}', [PaymentController::class, 'status'])
            ->name('payments.status');

        // List all payments (with filters)
        Route::get('/', [PaymentController::class, 'index'])
            ->name('payments.index')
            ->middleware('can:viewAny,App\Models\Payment');

        // Export payments
        Route::get('/export', [PaymentController::class, 'export'])
            ->name('payments.export')
            ->middleware('can:export,App\Models\Payment');

        // Record an offline payment
        Route::post('/record-offline', [PaymentController::class, 'recordOfflinePayment'])
            ->name('payments.record-offline')
            ->middleware('can:recordOffline,App\Models\Payment');

        // Get payment details
        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('payments.show')
            ->middleware('can:view,payment');

        // Update payment status (admin only)
        Route::put('/{payment}/status', [PaymentController::class, 'updateStatus'])
            ->name('payments.update.status')
            ->middleware('can:updateStatus,payment');
    });

    // Payment gateway management (admin only)
    Route::apiResource('payment-gateways', \App\Http\Controllers\PaymentGatewayController::class)
        ->except(['create', 'edit'])
        ->middleware('can:manageGateways,App\Models\Payment');
});
