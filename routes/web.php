<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\OfficeLocationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayOffSwapRequestController;
use App\Http\Controllers\Kadiv\ApprovalController;
use App\Http\Controllers\Kadiv\AttendanceController as KadivAttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShiftSwapRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', fn () => Auth::check() ? redirect('/dashboard') : redirect('/login'));

// Guest (auth)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Password reset
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::get('/notifications/unread', [NotificationController::class, 'unreadJson'])->name('notifications.unread');

    // Attendance (semua role yang bisa absen)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    // Shift Swap (semua role)
    Route::resource('shift-swaps', ShiftSwapRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/shift-swaps/{shiftSwap}/cancel', [ShiftSwapRequestController::class, 'cancel'])->name('shift-swaps.cancel');
    Route::post('/shift-swaps/{shiftSwap}/target-respond', [ShiftSwapRequestController::class, 'targetRespond'])->name('shift-swaps.targetRespond');

    // Day Off Swap
    Route::resource('day-off-swaps', DayOffSwapRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/day-off-swaps/{dayOffSwap}/cancel', [DayOffSwapRequestController::class, 'cancel'])->name('day-off-swaps.cancel');

    // KD: lihat absensi anggota divisi sendiri + approval swap
    Route::middleware('role:kepala_divisi')->prefix('kadiv')->name('kadiv.')->group(function () {
        Route::get('/attendances', [KadivAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/{attendance}', [KadivAttendanceController::class, 'show'])->name('attendances.show');

        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/{swapType}/{id}', [ApprovalController::class, 'show'])->name('approvals.show');
        Route::post('/approvals/{swapType}/{id}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');
    });

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('divisions', DivisionController::class)->except(['show']);
        Route::resource('employees', EmployeeController::class);
        Route::resource('shifts', ShiftController::class)->except(['show']);
        Route::resource('schedules', ScheduleController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('holidays', HolidayController::class);

        // Attendance list + detail (foto, lokasi via Leaflet)
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendances.show');

        Route::get('/office-locations', [OfficeLocationController::class, 'index'])->name('office-locations.index');
        Route::get('/office-locations/edit', [OfficeLocationController::class, 'edit'])->name('office-locations.edit');
        Route::put('/office-locations', [OfficeLocationController::class, 'update'])->name('office-locations.update');

        // Reports & audit
        Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/monthly/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
