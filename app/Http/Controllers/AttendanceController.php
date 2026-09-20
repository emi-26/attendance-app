<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * 一般ユーザーの勤怠登録画面を表示する。
     *
     * @param  Request  $request  認証済みユーザーのリクエスト
     * @return View 勤怠登録画面
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo')->locale('ja');

        return view('user.attendance-register', [
            'user' => $request->user(),
            'formattedDate' => $now->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => $now->format('H:i'),
        ]);
    }

    /**
     * 出勤・休憩・退勤の打刻処理を実行する。
     *
     * @param  Request  $request  打刻内容を含むリクエスト
     * @return RedirectResponse 勤怠登録画面へのリダイレクト
     */
    public function store(Request $request): RedirectResponse
    {
        $now = Carbon::now('Asia/Tokyo');
        $user = $request->user();

        $attendanceRecord = $user->attendanceRecords()
            ->with('breaks')
            ->whereDate('date', $now->toDateString())
            ->first();

        switch ($request->input('action')) {
            case 'clock_in':
                $this->clockIn($user->id, $attendanceRecord, $now);
                break;

            case 'break_in':
                $this->breakIn($attendanceRecord, $now);
                break;

            case 'break_out':
                $this->breakOut($attendanceRecord, $now);
                break;

            case 'clock_out':
                $this->clockOut($attendanceRecord, $now);
                break;
        }

        return redirect('/attendance');
    }

    private function clockIn(
        int $userId,
        ?AttendanceRecord $record,
        Carbon $now
    ): void {
        if ($record) {
            return;
        }

        AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $now->toDateString(),
            'clock_in' => $now->format('H:i:s'),
        ]);
    }

    private function breakIn(
        ?AttendanceRecord $record,
        Carbon $now
    ): void {
        if (! $record || ! $record->clock_in || $record->clock_out) {
            return;
        }

        if ($record->breaks()->whereNull('break_out')->exists()) {
            return;
        }

        $record->breaks()->create([
            'break_in' => $now->format('H:i:s'),
        ]);
    }

    private function breakOut(
        ?AttendanceRecord $record,
        Carbon $now
    ): void {
        if (! $record || $record->clock_out) {
            return;
        }

        $break = $record->breaks()
            ->whereNull('break_out')
            ->latest('id')
            ->first();

        if (! $break) {
            return;
        }

        $break->update([
            'break_out' => $now->format('H:i:s'),
        ]);
    }

    private function clockOut(
        ?AttendanceRecord $record,
        Carbon $now
    ): void {
        if (! $record || ! $record->clock_in || $record->clock_out) {
            return;
        }

        if ($record->breaks()->whereNull('break_out')->exists()) {
            return;
        }

        $record->update([
            'clock_out' => $now->format('H:i:s'),
        ]);
    }
}
