<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AdminAttendanceService
{
    /**
     * 勤怠記録の休憩時間を表示用文字列で取得する。
     *
     * @param  AttendanceRecord  $record  集計対象の勤怠記録
     * @return string 休憩時間
     */
    public function calculateBreakTime(
        AttendanceRecord $record
    ): string {
        $minutes = $record->breaks
            ->filter(
                fn ($break): bool => $break->break_in && $break->break_out
            )
            ->sum(
                fn ($break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $minutes > 0
            ? $this->formatMinutes($minutes)
            : '';
    }

    /**
     * 勤怠記録の実労働時間を表示用文字列で取得する。
     *
     * @param  AttendanceRecord  $record  集計対象の勤怠記録
     * @return string 実労働時間
     */
    public function calculateWorkTime(
        AttendanceRecord $record
    ): string {
        if (! $record->clock_in || ! $record->clock_out) {
            return '';
        }

        $workMinutes = Carbon::parse($record->clock_in)
            ->diffInMinutes(
                Carbon::parse($record->clock_out)
            );

        $breakMinutes = $record->breaks
            ->filter(
                fn ($break): bool => $break->break_in && $break->break_out
            )
            ->sum(
                fn ($break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $this->formatMinutes(
            $workMinutes - $breakMinutes
        );
    }

    /**
     * 分数をHH:MM:SS形式に変換する。
     *
     * @param  int  $minutes  分単位の時間
     * @return string HH:MM:SS形式の時間
     */
    public function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d:00',
            intdiv($minutes, 60),
            $minutes % 60
        );
    }

    /**
     * 時刻をH:i形式に整形する。
     *
     * @param  string|null  $time  整形対象の時刻
     * @return string 整形後の時刻
     */
    public function formatTime(?string $time): string
    {
        return $time
            ? Carbon::parse($time)->format('H:i')
            : '';
    }
}
