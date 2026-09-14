<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

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
