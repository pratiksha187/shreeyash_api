<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DailyProgressReportController;
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
    Route::get('/dprs', [DailyProgressReportController::class, 'index'])->name('api.dprs.index');
    Route::post('/dprs', [DailyProgressReportController::class, 'store'])->name('api.dprs.store');
    Route::get('/dprs/{dailyProgressReport}', [DailyProgressReportController::class, 'show'])->name('api.dprs.show');
    Route::get('/dpr', [DailyProgressReportController::class, 'index'])->name('api.dpr.index');
    Route::post('/dpr', [DailyProgressReportController::class, 'store'])->name('api.dpr.store');
    Route::get('/dpr/{dailyProgressReport}', [DailyProgressReportController::class, 'show'])->name('api.dpr.show');
    Route::get('/dpr-photos/{photo}', [DailyProgressReportController::class, 'photo'])->name('api.dpr-photos.show');
    Route::post('/payments', [PaymentController::class, 'store'])->name('api.payments.store');
    Route::get('/payments', [PaymentController::class, 'index'])->name('api.payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('api.payments.show');
    Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])->name('api.payments.slip');
    Route::get('/payments/{payment}/slip-data', [PaymentController::class, 'slipData'])->name('api.payments.slip-data');
});
