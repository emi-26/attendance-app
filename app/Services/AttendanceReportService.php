<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    /**
     * レポート集計に必要なサービスを受け取る。
     *
     * @param  WorkTimeCalculator  $workTimeCalculator  勤怠時間計算サービス
     * @param  AttendanceAnomalyService  $attendanceAnomalyService  異常勤務集計サービス
     */
    public function __construct(
        private WorkTimeCalculator $workTimeCalculator,
        private AttendanceAnomalyService $attendanceAnomalyService
    ) {}

    /**
     * 勤怠レポートの集計結果を作成する。
     *
     * @param  Carbon  $startMonth  集計期間の開始月
     * @param  Carbon  $endMonth  集計期間の終了月
     * @param  Collection<int, AttendanceRecord>  $records  集計対象の勤怠記録
     * @return array<string, mixed> 勤怠レポートの集計結果
     */
    public function buildReport(
        Carbon $startMonth,
        Carbon $endMonth,
        Collection $records
    ): array {
        $workMinutes = $records->mapWithKeys(
            fn (AttendanceRecord $record): array => [
                $record->id => $this->workTimeCalculator
                    ->calculate($record),
            ]
        );

        return [
            'summary' => $this->buildSummary($workMinutes),
            'monthlyTrend' => $this->buildMonthlyTrend(
                $startMonth,
                $records,
                $workMinutes
            ),
            'anomalies' => $this->attendanceAnomalyService->build(
                $endMonth,
                $records
            ),
        ];
    }

    /**
     * 勤怠サマリーを集計する。
     *
     * @param  Collection<int, int>  $workMinutes  勤怠記録ごとの実労働時間
     * @return array<string, int> 総労働時間・残業時間・平均労働時間
     */
    private function buildSummary(
        Collection $workMinutes
    ): array {
        $total = $workMinutes->sum();

        $overtime = $workMinutes->sum(
            fn (int $minutes): int => $this->workTimeCalculator
                ->calculateOvertime($minutes)
        );

        $workedDays = $workMinutes
            ->filter(
                fn (int $minutes): bool => $minutes > 0
            )
            ->count();

        return [
            'total_work_minutes' => $total,
            'total_overtime_minutes' => $overtime,
            'avg_work_minutes' => $workedDays > 0
                ? intdiv($total, $workedDays)
                : 0,
        ];
    }

    /**
     * 過去6ヶ月の月次推移を集計する。
     *
     * @param  Carbon  $startMonth  集計期間の開始月
     * @param  Collection<int, AttendanceRecord>  $records  集計対象の勤怠記録
     * @param  Collection<int, int>  $workMinutes  勤怠記録ごとの実労働時間
     * @return Collection<int, array<string, int|string>> 月次集計
     */
    private function buildMonthlyTrend(
        Carbon $startMonth,
        Collection $records,
        Collection $workMinutes
    ): Collection {
        return collect(range(0, 5))->map(
            function (int $index) use (
                $startMonth,
                $records,
                $workMinutes
            ): array {
                $month = $startMonth
                    ->copy()
                    ->addMonths($index);

                $monthlyRecords = $records->filter(
                    fn (AttendanceRecord $record): bool => Carbon::parse($record->date)
                        ->format('Y-m') ===
                        $month->format('Y-m')
                );

                return [
                    'month' => $month->format('Y/m'),
                    'work_minutes' => $monthlyRecords->sum(
                        fn (AttendanceRecord $record): int => $workMinutes->get($record->id, 0)
                    ),
                    'overtime_minutes' => $monthlyRecords->sum(
                        fn (AttendanceRecord $record): int => $this->workTimeCalculator
                            ->calculateOvertime(
                                $workMinutes->get($record->id, 0)
                            )
                    ),
                ];
            }
        );
    }
}
