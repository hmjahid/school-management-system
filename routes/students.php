<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
 * Student API routes. Mounted under /api/v1 with the standard api envelope
 * middleware. Authorization is enforced per-action via policies inside the
 * controller, so no blanket auth middleware is applied here.
 */

Route::apiResource('students', StudentController::class)->names('api.students');

Route::get('students/{student}/attendance', static fn () => response()->json(['data' => []]))
    ->name('api.students.attendance.index');

Route::get('students/{student}/results', static fn () => response()->json(['data' => []]))
    ->name('api.students.results.index');

Route::get('students/{student}/fees', static fn () => response()->json(['data' => []]))
    ->name('api.students.fees.index');

Route::get('students/{student}/edit', static fn () => response()->json(['data' => null]))
    ->name('admin.students.edit');
