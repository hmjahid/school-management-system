<?php

use App\Http\Controllers\RefundController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/refunds', [RefundController::class, 'index'])->middleware('role:admin')->name('api.refunds.index');
    Route::get('/refunds/statistics', [RefundController::class, 'statistics'])->middleware('role:admin')->name('api.refunds.statistics');
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])->name('api.refunds.show');
    Route::post('/refunds/{refund}/process', [RefundController::class, 'process'])->middleware('role:admin')->name('api.refunds.process');
    Route::post('/refunds/{refund}/cancel', [RefundController::class, 'cancel'])->middleware('role:admin')->name('api.refunds.cancel');
    Route::post('/payments/{payment}/refunds', [RefundController::class, 'store'])->middleware('role:admin')->name('api.refunds.store');
});
