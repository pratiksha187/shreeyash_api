<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(AuthenticateApiToken::class)->group(function () {
    Route::get('/user', [AuthController::class, 'profile'])->name('user');
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/attendance/me', [AttendanceController::class, 'me']);
    Route::get('/attendance/daily-report', [AttendanceController::class, 'dailyReport']);
    Route::post('/attendance/leave', [AttendanceController::class, 'applyLeave']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::post('/attendance/update', [AttendanceController::class, 'update']);
    Route::get('/payments', [PaymentController::class, 'index'])->name('api.payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('api.payments.show');
    Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])->name('api.payments.slip');
});
