<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStampCorrectionRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceExportController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth',
    'verified',
])->group(function () {
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

    Route::get('/attendance/report', [
        AttendanceReportController::class,
        'index',
    ]);

    Route::get('/attendance/detail/{id}', [
        AttendanceDetailController::class,
        'show',
    ]);

    Route::post('/attendance/{id}', [
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
});

Route::middleware('auth')->get(
    '/attendance/{attendanceRecord}',
    function (
        Request $request,
        int $attendanceRecord
    ) {
        if ($request->user()->admin_status) {
            return redirect(
                '/admin/attendance/'.$attendanceRecord
            );
        }

        return redirect(
            '/attendance/detail/'.$attendanceRecord
        );
    }
);

Route::middleware([
    'auth',
    'admin',
])->group(function () {
    Route::get('/admin/attendance/list', [
        AdminAttendanceController::class,
        'index',
    ]);

    Route::get('/admin/attendance/{id}', [
        AdminAttendanceController::class,
        'show',
    ]);

    Route::post('/admin/attendance/{id}', [
        AdminAttendanceController::class,
        'update',
    ]);

    Route::get('/admin/staff/list', [
        AdminStaffController::class,
        'index',
    ]);

    Route::get('/admin/attendance/staff/{id}', [
        AdminStaffController::class,
        'show',
    ]);

    Route::post('/export', [
        AttendanceExportController::class,
        'export',
    ]);

    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [
            AdminStampCorrectionRequestController::class,
            'show',
        ]
    );

    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [
            AdminStampCorrectionRequestController::class,
            'approve',
        ]
    );
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [
        AdminAuthController::class,
        'create',
    ]);

    Route::post('/admin/login', [
        AuthenticatedSessionController::class,
        'store',
    ]);
});

Route::post('/admin/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware([
    'auth',
    'admin',
]);
