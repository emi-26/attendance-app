<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AdminAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private AdminAttendanceService $adminAttendanceService
    ) {}

    /**
     * 指定日の一般ユーザー勤怠一覧を表示する。
     *
     * @return View 管理者用勤怠一覧画面
     */
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
            $record->total_break_time =
                $this->adminAttendanceService
                    ->calculateBreakTime($record);

            $record->total_time =
                $this->adminAttendanceService
                    ->calculateWorkTime($record);
        }

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDay' => $date->copy()->addDay()->format('Y-m-d'),
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    /**
     * 指定した勤怠の詳細を表示する。
     *
     * @param  int  $id  勤怠記録ID
     * @return View 管理者用勤怠詳細画面
     */
    public function show(int $id): View
    {
        $attendanceRecord = AttendanceRecord::with('breaks')
            ->findOrFail($id);

        $date = Carbon::parse($attendanceRecord->date);

        $data = [
            'id' => $attendanceRecord->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'clock_in' => $this->adminAttendanceService
                ->formatTime($attendanceRecord->clock_in),
            'clock_out' => $this->adminAttendanceService
                ->formatTime($attendanceRecord->clock_out),
            'breaks' => $attendanceRecord->breaks
                ->map(function ($break): array {
                    return [
                        'break_in' => $this->adminAttendanceService
                            ->formatTime($break->break_in),
                        'break_out' => $this->adminAttendanceService
                            ->formatTime($break->break_out),
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

    /**
     * 管理者が勤怠情報を直接更新する。
     *
     * @param  AttendanceCorrectionRequest  $request  検証済みの勤怠修正内容
     * @param  int  $id  勤怠記録ID
     * @return RedirectResponse 更新後の勤怠詳細画面へのリダイレクト
     */
    public function update(
        AttendanceCorrectionRequest $request,
        int $id
    ): RedirectResponse {
        $attendanceRecord = AttendanceRecord::findOrFail($id);

        DB::transaction(
            function () use (
                $request,
                $attendanceRecord
            ): void {
                $attendanceRecord->update([
                    'clock_in' => $request->input('new_clock_in'),
                    'clock_out' => $request->input('new_clock_out'),
                    'comment' => $request->input('comment'),
                ]);

                $attendanceRecord->breaks()->delete();

                $breakIns = $request->input(
                    'new_break_in',
                    []
                );

                $breakOuts = $request->input(
                    'new_break_out',
                    []
                );

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
            }
        );

        return redirect(
            '/admin/attendance/'.$attendanceRecord->id
        );
    }
}
