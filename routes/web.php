<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\ChallanController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\DailyProgressReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FddTestRecordController;
use App\Http\Controllers\Admin\LabourAttendanceController;
use App\Http\Controllers\Admin\MissedAttendanceRequestController;
use App\Http\Controllers\Admin\MirFileReportController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VehicleLogController;
use App\Http\Middleware\EnsureAdminLoggedIn;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::middleware(EnsureAdminLoggedIn::class)->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/attendance-reports', [AttendanceReportController::class, 'index'])->name('attendance-reports.index');
        Route::get('/dpr-reports', [DailyProgressReportController::class, 'index'])->name('dpr-reports.index');
        Route::get('/dpr-reports/photos/{photo}', [DailyProgressReportController::class, 'photo'])->name('dpr-reports.photo');
        Route::get('/fdd-test-records', [FddTestRecordController::class, 'index'])->name('fdd-test-records.index');
        Route::post('/fdd-test-records', [FddTestRecordController::class, 'store'])->name('fdd-test-records.store');
        Route::post('/fdd-road-sections', [FddTestRecordController::class, 'storeRoadSection'])->name('fdd-road-sections.store');
        Route::get('/fdd-test-records/export', [FddTestRecordController::class, 'export'])->name('fdd-test-records.export');
        Route::get('/fdd-test-records/{fddTestRecord}/edit', [FddTestRecordController::class, 'edit'])->name('fdd-test-records.edit');
        Route::put('/fdd-test-records/{fddTestRecord}', [FddTestRecordController::class, 'update'])->name('fdd-test-records.update');
        Route::delete('/fdd-test-records/{fddTestRecord}', [FddTestRecordController::class, 'destroy'])->name('fdd-test-records.destroy');
        Route::get('/mir-file-reports', [MirFileReportController::class, 'index'])->name('mir-file-reports.index');
        Route::post('/mir-file-reports', [MirFileReportController::class, 'store'])->name('mir-file-reports.store');
        Route::get('/mir-file-reports/export', [MirFileReportController::class, 'export'])->name('mir-file-reports.export');
        Route::get('/mir-file-reports/{mirFileReport}/edit', [MirFileReportController::class, 'edit'])->name('mir-file-reports.edit');
        Route::put('/mir-file-reports/{mirFileReport}', [MirFileReportController::class, 'update'])->name('mir-file-reports.update');
        Route::delete('/mir-file-reports/{mirFileReport}', [MirFileReportController::class, 'destroy'])->name('mir-file-reports.destroy');
        Route::get('/challans', [ChallanController::class, 'index'])->name('challans.index');
        Route::get('/challans/export', [ChallanController::class, 'export'])->name('challans.export');
        Route::get('/challans/{challan}/download', [ChallanController::class, 'download'])->name('challans.download');
        Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::patch('/complaints/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');
        Route::get('/missed-requests', [MissedAttendanceRequestController::class, 'index'])->name('missed-requests.index');
        Route::patch('/missed-requests/{missedAttendanceRequest}', [MissedAttendanceRequestController::class, 'update'])->name('missed-requests.update');
        Route::get('/labour-attendance', [LabourAttendanceController::class, 'index'])->name('labour-attendance.index');
        Route::post('/labour-sites', [LabourAttendanceController::class, 'storeSite'])->name('labour-sites.store');
        Route::post('/contractors', [LabourAttendanceController::class, 'storeContractor'])->name('contractors.store');
        Route::post('/labours', [LabourAttendanceController::class, 'storeLabour'])->name('labours.store');
        Route::get('/labour-attendance/{labourAttendance}/photo', [LabourAttendanceController::class, 'photo'])->name('labour-attendance.photo');
        Route::patch('/labour-attendance/{labourAttendance}', [LabourAttendanceController::class, 'update'])->name('labour-attendance.update');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/generate', [PaymentController::class, 'generate'])->name('payments.generate');
        Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])->name('payments.slip');
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::post('/vehicles/{vehicle}/monthly-entries', [VehicleLogController::class, 'storeMonthly'])->name('vehicles.logs.monthly');
        Route::post('/vehicles/{vehicle}/logs', [VehicleLogController::class, 'store'])->name('vehicles.logs.store');
        Route::get('/vehicles/{vehicle}/logs/{vehicleLog}/edit', [VehicleLogController::class, 'edit'])->name('vehicles.logs.edit');
        Route::put('/vehicles/{vehicle}/logs/{vehicleLog}', [VehicleLogController::class, 'update'])->name('vehicles.logs.update');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    });
});
