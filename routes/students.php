<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
 * Student API routes. Mounted under /api/v1 with the standard api envelope
 * middleware. Authorization is enforced per-action via policies inside the
 * controller, so no blanket auth middleware is applied here.
 */

Route::apiResource('students', StudentController::class)->names('api.students');

Route::get('students/{student}/attendance', [StudentController::class, 'attendance'])
    ->name('api.students.attendance.index');

Route::get('students/{student}/results', [StudentController::class, 'results'])
    ->name('api.students.results.index');

Route::get('students/{student}/fees', [StudentController::class, 'fees'])
    ->name('api.students.fees.index');

Route::get('students/{student}/edit', [StudentController::class, 'edit'])
    ->name('admin.students.edit');
