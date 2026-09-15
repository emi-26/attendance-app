<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $endMonth = Carbon::now('Asia/Tokyo')->startOfMonth();
        $startMonth = $endMonth->copy()->subMonths(5);

        $attendanceRecords = AttendanceRecord::with('breaks')
            ->where('user_id', $request->user()->id)
            ->whereBetween('date', [
                $startMonth->copy()->startOfMonth()->toDateString(),
                $endMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        $workMinutesByRecord = $attendanceRecords->mapWithKeys(
            fn (AttendanceRecord $record): array => [
                $record->id => $this->calculateWorkMinutes($record),
            ]
        );

        $totalWorkMinutes = $workMinutesByRecord->sum();

        $totalOvertimeMinutes = $workMinutesByRecord->sum(
            fn (int $minutes): int => max(0, $minutes - 480)
        );

        $workedDays = $workMinutesByRecord
            ->filter(fn (int $minutes): bool => $minutes > 0)
            ->count();

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $workedDays > 0
                ? intdiv($totalWorkMinutes, $workedDays)
                : 0,
        ];

        $monthlyTrend = collect(range(0, 5))
            ->map(function (int $index) use (
                $startMonth,
                $attendanceRecords,
                $workMinutesByRecord
            ): array {
                $month = $startMonth->copy()->addMonths($index);

                $monthlyRecords = $attendanceRecords->filter(
                    fn (AttendanceRecord $record): bool => Carbon::parse($record->date)->format('Y-m')
                        === $month->format('Y-m')
                );

                $workMinutes = $monthlyRecords->sum(
                    fn (AttendanceRecord $record): int => $workMinutesByRecord->get($record->id, 0)
                );

                $overtimeMinutes = $monthlyRecords->sum(
                    fn (AttendanceRecord $record): int => max(
                        0,
                        $workMinutesByRecord->get($record->id, 0) - 480
                    )
                );

                return [
                    'month' => $month->format('Y/m'),
                    'work_minutes' => $workMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                ];
            });

        $currentMonthRecords = $attendanceRecords->filter(
            fn (AttendanceRecord $record): bool => Carbon::parse($record->date)->format('Y-m')
                === $endMonth->format('Y-m')
        );

        $anomalies = [
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
                    fn (AttendanceRecord $record): bool => $this->calculateWorkMinutes($record) > 600
                )
                ->count(),
        ];

        return view('reports.index', [
            'summary' => $summary,
            'monthlyTrend' => $monthlyTrend,
            'anomalies' => $anomalies,
        ]);
    }

    private function calculateWorkMinutes(
        AttendanceRecord $attendanceRecord
    ): int {
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

    private function isLate(AttendanceRecord $attendanceRecord): bool
    {
        if (! $attendanceRecord->clock_in) {
            return false;
        }

        return Carbon::parse($attendanceRecord->clock_in)
            ->format('H:i:s') > '09:00:00';
    }

    private function isEarlyLeave(
        AttendanceRecord $attendanceRecord
    ): bool {
        if (! $attendanceRecord->clock_out) {
            return false;
        }

        return Carbon::parse($attendanceRecord->clock_out)
            ->format('H:i:s') < '18:00:00';
    }
}
