<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStampCorrectionRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [
        AttendanceController::class,
        'index',
    ]);

    Route::post('/attendance', [
        AttendanceController::class,
        'store',
    ]);

    Route::get('/attendance/list', [
        AttendanceListController::class,
        'index',
    ]);

    Route::get('/attendance/detail/{attendanceRecord}', [
        AttendanceDetailController::class,
        'show',
    ]);

    Route::get('/attendance/{attendanceRecord}', [
        AttendanceDetailController::class,
        'show',
    ]);

    Route::post('/attendance/{attendanceRecord}', [
        AttendanceDetailController::class,
        'store',
    ]);

    Route::get('/stamp_correction_request/list', [
        StampCorrectionRequestController::class,
        'index',
    ]);

    Route::get('/application/{application}', [
        StampCorrectionRequestController::class,
        'show',
    ]);

    Route::get('/admin/attendance/list', [
        AdminAttendanceController::class,
        'index',
    ]);

    Route::get('/admin/attendance/{attendanceRecord}', [
        AdminAttendanceController::class,
        'show',
    ]);

    Route::post('/admin/attendance/{attendanceRecord}', [
        AdminAttendanceController::class,
        'update',
    ]);

    Route::get('/admin/staff/list', [
        AdminStaffController::class,
        'index',
    ]);

    Route::get('/admin/attendance/staff/{user}', [
        AdminStaffController::class,
        'show',
    ]);

    Route::get(
        '/stamp_correction_request/approve/{application}',
        [
            AdminStampCorrectionRequestController::class,
            'show',
        ]
    );

    Route::post(
        '/stamp_correction_request/approve/{application}',
        [
            AdminStampCorrectionRequestController::class,
            'approve',
        ]
    );
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [
        AdminAuthController::class,
        'showLoginForm',
    ]);

    Route::post('/admin/login', [
        AuthenticatedSessionController::class,
        'store',
    ]);
});

Route::post('/admin/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware('auth');
