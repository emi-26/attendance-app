<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    /**
     * 勤怠記録のユーザーを取得する。
     *
     * @return BelongsTo ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤怠記録の休憩情報を取得する。
     *
     * @return HasMany 休憩情報とのリレーション
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * 勤怠記録に対する修正申請を取得する。
     *
     * @return HasMany 修正申請とのリレーション
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 合計休憩時間を取得する。
     *
     * @return string HH:MM形式の休憩時間
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $breakMinutes = $this->breaks
            ->filter(
                fn (AttendanceBreak $break): bool => $break->break_in !== null &&
                    $break->break_out !== null
            )
            ->sum(
                fn (AttendanceBreak $break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $this->formatMinutes($breakMinutes);
    }

    /**
     * 実労働時間を取得する。
     *
     * @return string HH:MM形式の実労働時間
     */
    public function getTotalTimeAttribute(): string
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return '';
        }

        $workMinutes = Carbon::parse($this->clock_in)
            ->diffInMinutes(
                Carbon::parse($this->clock_out)
            );

        $breakMinutes = $this->breaks
            ->filter(
                fn (AttendanceBreak $break): bool => $break->break_in !== null &&
                    $break->break_out !== null
            )
            ->sum(
                fn (AttendanceBreak $break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $this->formatMinutes(
            $workMinutes - $breakMinutes
        );
    }

    /**
     * 分数をHH:MM形式に変換する。
     *
     * @param  int  $minutes  分単位の時間
     * @return string HH:MM形式の時間
     */
    private function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d',
            intdiv($minutes, 60),
            $minutes % 60
        );
    }
}
