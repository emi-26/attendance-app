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
    /**
     * 指定した勤怠の詳細を表示する。
     *
     * @param  Request  $request  認証済みユーザーのリクエスト
     * @param  int  $id  勤怠記録ID
     * @return View 勤怠詳細画面
     */
    public function show(
        Request $request,
        int $id
    ): View {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        if ($request->user()->admin_status) {
            return app(AdminAttendanceController::class)
                ->show($attendanceRecord->id);
        }

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

    /**
     * 一般ユーザーの勤怠修正申請を登録する。
     *
     * @param  AttendanceCorrectionRequest  $request  検証済みの修正内容
     * @param  int  $id  勤怠記録ID
     * @return RedirectResponse 申請一覧画面へのリダイレクト
     */
    public function store(
        AttendanceCorrectionRequest $request,
        int $id
    ): RedirectResponse {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        if ($request->user()->admin_status) {
            return app(AdminAttendanceController::class)
                ->update($request, $attendanceRecord->id);
        }

        $this->authorizeRecord($request, $attendanceRecord);

        $hasPendingApplication = $attendanceRecord->applications()
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApplication) {
            return redirect(
                '/attendance/detail/'.$attendanceRecord->id
            );
        }

        DB::transaction(function () use ($request, $attendanceRecord): void {
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

    /**
     * 勤怠記録がログインユーザー本人のものか確認する。
     *
     * @param  Request  $request  認証済みユーザーのリクエスト
     * @param  AttendanceRecord  $attendanceRecord  確認対象の勤怠記録
     */
    private function authorizeRecord(
        Request $request,
        AttendanceRecord $attendanceRecord
    ): void {
        abort_unless(
            $attendanceRecord->user_id === $request->user()->id,
            403
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
