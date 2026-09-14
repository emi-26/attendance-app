<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStampCorrectionRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー用ルート
|--------------------------------------------------------------------------
|
| ログイン済み、かつメール認証済みの一般ユーザーだけ利用できます。
|
*/
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

    Route::get('/attendance/detail/{attendanceRecord}', [
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
});

/*
|--------------------------------------------------------------------------
| 一般・管理者共通の勤怠詳細振り分け
|--------------------------------------------------------------------------
|
| ログインしているユーザーが管理者なら管理者用詳細へ、
| 一般ユーザーなら一般ユーザー用詳細へ移動します。
|
*/
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

/*
|--------------------------------------------------------------------------
| 管理者用ルート
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth',
    'admin',
])->group(function () {
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

/*
|--------------------------------------------------------------------------
| 管理者認証
|--------------------------------------------------------------------------
*/
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
])->middleware([
    'auth',
    'admin',
]);
