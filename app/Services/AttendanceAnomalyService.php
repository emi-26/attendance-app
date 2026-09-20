<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceAnomalyService
{
    private const STANDARD_CLOCK_IN = '09:00:00';

    private const STANDARD_CLOCK_OUT = '18:00:00';

    /**
     * 勤怠時間計算サービスを受け取る。
     *
     * @param  WorkTimeCalculator  $workTimeCalculator  勤怠時間計算サービス
     */
    public function __construct(
        private WorkTimeCalculator $workTimeCalculator
    ) {}

    /**
     * 当月の異常勤務回数を集計する。
     *
     * @param  Carbon  $endMonth  集計対象月
     * @param  Collection<int, AttendanceRecord>  $records  集計対象の勤怠記録
     * @return array<string, int> 遅刻・早退・長時間労働の回数
     */
    public function build(
        Carbon $endMonth,
        Collection $records
    ): array {
        $currentMonthRecords = $records->filter(
            fn (AttendanceRecord $record): bool => Carbon::parse($record->date)->format('Y-m') ===
                $endMonth->format('Y-m')
        );

        return [
            'late_count' => $currentMonthRecords
                ->filter(
                    fn (AttendanceRecord $record): bool => $this->isLate($record)
                )
                ->count(),

            'early_leave_count' => $currentMonthRecords
                ->filter(
                    fn (AttendanceRecord $record): bool => $this->isEarlyLeave($record)
                )
                ->count(),

            'long_work_count' => $currentMonthRecords
                ->filter(
                    fn (AttendanceRecord $record): bool => $this->workTimeCalculator->isLongWork(
                        $this->workTimeCalculator->calculate($record)
                    )
                )
                ->count(),
        ];
    }

    /**
     * 9時を過ぎて出勤したか判定する。
     *
     * @param  AttendanceRecord  $record  判定対象の勤怠記録
     * @return bool 遅刻の場合はtrue
     */
    private function isLate(AttendanceRecord $record): bool
    {
        return $record->clock_in
            && Carbon::parse($record->clock_in)->format('H:i:s') >
                self::STANDARD_CLOCK_IN;
    }

    /**
     * 18時より前に退勤したか判定する。
     *
     * @param  AttendanceRecord  $record  判定対象の勤怠記録
     * @return bool 早退の場合はtrue
     */
    private function isEarlyLeave(AttendanceRecord $record): bool
    {
        return $record->clock_out
            && Carbon::parse($record->clock_out)->format('H:i:s') <
                self::STANDARD_CLOCK_OUT;
    }
}
