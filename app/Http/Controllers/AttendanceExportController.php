<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $user = User::where('admin_status', false)
            ->findOrFail($request->input('user_id'));

        $date = Carbon::createFromFormat(
            'Y-m',
            $request->input('year_month'),
            'Asia/Tokyo'
        );

        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date')
            ->get();

        $fileName = sprintf(
            '%s_%s.csv',
            $user->name,
            $date->format('Y-m')
        );

        return response()->streamDownload(
            function () use ($attendanceRecords): void {
                $stream = fopen('php://output', 'w');

                fwrite($stream, "\xEF\xBB\xBF");

                fputcsv($stream, [
                    '日付',
                    '出勤',
                    '退勤',
                    '休憩',
                    '合計',
                ]);

                foreach ($attendanceRecords as $attendanceRecord) {
                    fputcsv($stream, [
                        Carbon::parse($attendanceRecord->date)
                            ->format('Y/m/d'),
                        $this->formatTime(
                            $attendanceRecord->clock_in
                        ),
                        $this->formatTime(
                            $attendanceRecord->clock_out
                        ),
                        $this->calculateBreakTime(
                            $attendanceRecord
                        ),
                        $this->calculateWorkTime(
                            $attendanceRecord
                        ),
                    ]);
                }

                fclose($stream);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    private function calculateBreakTime(
        AttendanceRecord $attendanceRecord
    ): string {
        $minutes = $attendanceRecord->breaks
            ->filter(
                fn ($break) => $break->break_in && $break->break_out
            )
            ->sum(function ($break) {
                return Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    );
            });

        return $this->formatMinutes($minutes);
    }

    private function calculateWorkTime(
        AttendanceRecord $attendanceRecord
    ): string {
        if (
            ! $attendanceRecord->clock_in ||
            ! $attendanceRecord->clock_out
        ) {
            return '';
        }

        $workMinutes = Carbon::parse(
            $attendanceRecord->clock_in
        )->diffInMinutes(
            Carbon::parse($attendanceRecord->clock_out)
        );

        $breakMinutes = $attendanceRecord->breaks
            ->filter(
                fn ($break) => $break->break_in && $break->break_out
            )
            ->sum(function ($break) {
                return Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    );
            });

        return $this->formatMinutes(
            $workMinutes - $breakMinutes
        );
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d',
            intdiv($minutes, 60),
            $minutes % 60
        );
    }

    private function formatTime(?string $time): string
    {
        return $time
            ? Carbon::parse($time)->format('H:i')
            : '';
    }
}
