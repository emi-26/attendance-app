<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceListController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::createFromFormat(
                'Y-m',
                $request->input('date'),
                'Asia/Tokyo'
            )->startOfMonth()
            : Carbon::now('Asia/Tokyo')->startOfMonth();

        $records = $request->user()
            ->attendanceRecords()
            ->with('breaks')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get()
            ->keyBy(function (AttendanceRecord $record) {
                return Carbon::parse($record->date)->toDateString();
            });

        $formattedAttendanceRecords = [];

        $currentDate = $date->copy();
        $lastDate = $date->copy()->endOfMonth();

        while ($currentDate->lte($lastDate)) {
            $record = $records->get($currentDate->toDateString());

            $formattedAttendanceRecords[] = [
                'id' => $record?->id,
                'date' => $currentDate
                    ->copy()
                    ->locale('ja')
                    ->isoFormat('MM/DD(ddd)'),
                'clock_in' => $this->formatTime($record?->clock_in),
                'clock_out' => $this->formatTime($record?->clock_out),
                'total_break_time' => $record
                    ? $this->calculateBreakTime($record)
                    : '',
                'total_time' => $record
                    ? $this->calculateWorkTime($record)
                    : '',
            ];

            $currentDate->addDay();
        }

        return view('user.user-attendance-list', [
            'date' => $date,
            'previousMonth' => $date->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $date->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
        ]);
    }

    private function calculateBreakTime(
        AttendanceRecord $record
    ): string {
        $minutes = $record->breaks
            ->filter(fn ($break) => $break->break_in && $break->break_out)
            ->sum(function ($break) {
                return Carbon::parse($break->break_in)
                    ->diffInMinutes(Carbon::parse($break->break_out));
            });

        return $minutes > 0
            ? $this->formatMinutes($minutes)
            : '';
    }

    private function calculateWorkTime(
        AttendanceRecord $record
    ): string {
        if (! $record->clock_in || ! $record->clock_out) {
            return '';
        }

        $workMinutes = Carbon::parse($record->clock_in)
            ->diffInMinutes(Carbon::parse($record->clock_out));

        $breakMinutes = $record->breaks
            ->filter(fn ($break) => $break->break_in && $break->break_out)
            ->sum(function ($break) {
                return Carbon::parse($break->break_in)
                    ->diffInMinutes(Carbon::parse($break->break_out));
            });

        return $this->formatMinutes($workMinutes - $breakMinutes);
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d:00',
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
