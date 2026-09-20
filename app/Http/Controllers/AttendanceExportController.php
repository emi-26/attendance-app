<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    /**
     * 指定ユーザー・指定月の勤怠情報をCSV出力する。
     *
     * @param  Request  $request  ユーザーIDと対象年月を含むリクエスト
     * @return StreamedResponse CSVダウンロードレスポンス
     */
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

    /**
     * 勤怠記録の休憩時間を計算する。
     *
     * @param  AttendanceRecord  $attendanceRecord  集計対象の勤怠記録
     * @return string 休憩時間
     */
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

    /**
     * 勤怠記録の実労働時間を計算する。
     *
     * @param  AttendanceRecord  $attendanceRecord  集計対象の勤怠記録
     * @return string 実労働時間
     */
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

    /**
     * 時刻をH:i形式に整形する。
     *
     * @param  string|null  $time  整形対象の時刻
     * @return string 整形後の時刻
     */
    private function formatTime(?string $time): string
    {
        return $time
            ? Carbon::parse($time)->format('H:i')
            : '';
    }
}
