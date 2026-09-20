<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class WorkTimeCalculator
{
    private const STANDARD_WORK_MINUTES = 480;

    private const LONG_WORK_MINUTES = 600;

    /**
     * 勤怠記録から実労働時間を分単位で計算する。
     *
     * @param  AttendanceRecord  $attendanceRecord  計算対象の勤怠記録
     * @return int 休憩時間を除いた実労働時間
     */
    public function calculate(AttendanceRecord $attendanceRecord): int
    {
        if (
            ! $attendanceRecord->clock_in ||
            ! $attendanceRecord->clock_out
        ) {
            return 0;
        }

        $workMinutes = Carbon::parse($attendanceRecord->clock_in)
            ->diffInMinutes(
                Carbon::parse($attendanceRecord->clock_out)
            );

        $breakMinutes = $attendanceRecord->breaks
            ->filter(
                fn ($break): bool => $break->break_in && $break->break_out
            )
            ->sum(
                fn ($break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return max(0, $workMinutes - $breakMinutes);
    }

    /**
     * 1日8時間を超えた残業時間を分単位で計算する。
     *
     * @param  int  $workMinutes  実労働時間
     * @return int 残業時間
     */
    public function calculateOvertime(int $workMinutes): int
    {
        return max(
            0,
            $workMinutes - self::STANDARD_WORK_MINUTES
        );
    }

    /**
     * 実労働時間が10時間を超えているか判定する。
     *
     * @param  int  $workMinutes  実労働時間
     * @return bool 長時間労働の場合はtrue
     */
    public function isLongWork(int $workMinutes): bool
    {
        return $workMinutes > self::LONG_WORK_MINUTES;
    }
}
