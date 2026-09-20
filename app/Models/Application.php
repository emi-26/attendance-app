<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'clock_in',
        'clock_out',
        'comment',
        'status',
    ];

    /**
     * 申請者を取得する。
     *
     * @return BelongsTo 申請者とのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 申請対象の勤怠記録を取得する。
     *
     * @return BelongsTo 勤怠記録とのリレーション
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * 申請された休憩情報を取得する。
     *
     * @return HasMany 申請休憩情報とのリレーション
     */
    public function applicationBreaks(): HasMany
    {
        return $this->hasMany(ApplicationBreak::class);
    }
}
