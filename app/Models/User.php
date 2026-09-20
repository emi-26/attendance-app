<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    /**
     * ユーザーの勤怠記録を取得する。
     *
     * @return HasMany 勤怠記録とのリレーション
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * ユーザーの勤怠修正申請を取得する。
     *
     * @return HasMany 修正申請とのリレーション
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 当日の勤怠状態を取得する。
     *
     * @return string 勤務外・出勤中・休憩中・退勤済のいずれか
     */
    public function getAttendanceStatusAttribute(): string
    {
        $today = Carbon::now('Asia/Tokyo')->toDateString();

        $attendanceRecord = $this->attendanceRecords()
            ->with('breaks')
            ->whereDate('date', $today)
            ->first();

        if (! $attendanceRecord || ! $attendanceRecord->clock_in) {
            return '勤務外';
        }

        if ($attendanceRecord->clock_out) {
            return '退勤済';
        }

        $isOnBreak = $attendanceRecord->breaks
            ->contains(fn ($break) => is_null($break->break_out));

        if ($isOnBreak) {
            return '休憩中';
        }

        return '出勤中';
    }
}
