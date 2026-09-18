<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function index(): View
    {
        $date = request()->filled('date')
            ? Carbon::createFromFormat(
                'Y-m-d',
                request()->input('date'),
                'Asia/Tokyo'
            )
            : Carbon::now('Asia/Tokyo');

        $users = User::where('admin_status', false)->get();

        $attendanceRecords = AttendanceRecord::with('breaks')
            ->whereDate('date', $date->toDateString())
            ->get();

        foreach ($attendanceRecords as $record) {
            $record->total_break_time = $this->calculateBreakTime($record);
            $record->total_time = $this->calculateWorkTime($record);
        }

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDay' => $date->copy()->addDay()->format('Y-m-d'),
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    public function show(int $id): View
    {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        $attendanceRecord->load('breaks');

        $date = Carbon::parse($attendanceRecord->date);

        $data = [
            'id' => $attendanceRecord->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'clock_in' => $this->formatTime($attendanceRecord->clock_in),
            'clock_out' => $this->formatTime($attendanceRecord->clock_out),
            'breaks' => $attendanceRecord->breaks
                ->map(function ($break) {
                    return [
                        'break_in' => $this->formatTime($break->break_in),
                        'break_out' => $this->formatTime($break->break_out),
                    ];
                })
                ->values()
                ->toArray(),
            'comment' => $attendanceRecord->comment ?? '',
        ];

        return view('admin.admin-detail', [
            'user' => $attendanceRecord->user,
            'attendanceRecord' => $data,
        ]);
    }

    public function update(
        AttendanceCorrectionRequest $request,
        int $id
    ): RedirectResponse {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        DB::transaction(function () use ($request, $attendanceRecord): void {
            $attendanceRecord->update([
                'clock_in' => $request->input('new_clock_in'),
                'clock_out' => $request->input('new_clock_out'),
                'comment' => $request->input('comment'),
            ]);

            $attendanceRecord->breaks()->delete();

            $breakIns = $request->input('new_break_in', []);
            $breakOuts = $request->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                if (empty($breakIn) || empty($breakOut)) {
                    continue;
                }

                $attendanceRecord->breaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });

        return redirect('/admin/attendance/'.$attendanceRecord->id);
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
