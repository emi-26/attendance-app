<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    public function show(
        Request $request,
        AttendanceRecord $attendanceRecord
    ): View {
        $this->authorizeRecord($request, $attendanceRecord);

        $attendanceRecord->load('breaks');

        $application = $attendanceRecord->applications()
            ->with('applicationBreaks')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $date = Carbon::parse($attendanceRecord->date);

        $clockIn = $application
            ? $application->clock_in
            : $attendanceRecord->clock_in;

        $clockOut = $application
            ? $application->clock_out
            : $attendanceRecord->clock_out;

        $comment = $application
            ? $application->comment
            : $attendanceRecord->comment;

        $breaks = $application
            ? $application->applicationBreaks
            : $attendanceRecord->breaks;

        $data = [
            'id' => $attendanceRecord->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'clock_in' => $this->formatTime($clockIn),
            'clock_out' => $this->formatTime($clockOut),
            'breaks' => $breaks
                ->map(function ($break) {
                    return [
                        'break_in' => $this->formatTime($break->break_in),
                        'break_out' => $this->formatTime($break->break_out),
                    ];
                })
                ->values()
                ->toArray(),
            'comment' => $comment ?? '',
            'application' => $application,
        ];

        return view('user.user-detail', [
            'user' => $request->user(),
            'data' => $data,
        ]);
    }

    public function store(
        AttendanceCorrectionRequest $request,
        AttendanceRecord $attendanceRecord
    ): RedirectResponse {
        $this->authorizeRecord($request, $attendanceRecord);

        $hasPendingApplication = $attendanceRecord->applications()
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            return redirect(
                '/attendance/detail/'.$attendanceRecord->id
            );
        }

        DB::transaction(function () use ($request, $attendanceRecord) {
            $application = $attendanceRecord->applications()->create([
                'user_id' => $request->user()->id,
                'clock_in' => $request->input('new_clock_in'),
                'clock_out' => $request->input('new_clock_out'),
                'comment' => $request->input('comment'),
                'status' => 'pending',
            ]);

            $breakIns = $request->input('new_break_in', []);
            $breakOuts = $request->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                if (empty($breakIn) || empty($breakOut)) {
                    continue;
                }

                $application->applicationBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });

        return redirect('/stamp_correction_request/list');
    }

    private function authorizeRecord(
        Request $request,
        AttendanceRecord $attendanceRecord
    ): void {
        abort_unless(
            $attendanceRecord->user_id === $request->user()->id,
            403
        );
    }

    private function formatTime(?string $time): string
    {
        return $time
            ? Carbon::parse($time)->format('H:i')
            : '';
    }
}
