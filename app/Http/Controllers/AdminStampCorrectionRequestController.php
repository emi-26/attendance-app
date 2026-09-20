<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStampCorrectionRequestController extends Controller
{
    /**
     * 管理者用の申請一覧を表示する。
     *
     * @return View 管理者用申請一覧画面
     */
    public function index(): View
    {
        $applications = Application::with([
            'user',
            'attendanceRecord',
        ])
            ->latest()
            ->get();

        foreach ($applications as $application) {
            $application->approval_status = $application->status === 'approved'
                ? '承認済み'
                : '承認待ち';

            $application->application_date = $application->created_at;
        }

        return view('admin.admin-application-list', [
            'applications' => $applications,
        ]);
    }

    /**
     * 指定した修正申請の詳細を表示する。
     *
     * @param  int  $attendance_correct_request_id  修正申請ID
     * @return View 管理者用申請詳細画面
     */
    public function show(
        int $attendance_correct_request_id
    ): View {
        $application = Application::findOrFail(
            $attendance_correct_request_id
        );

        $application->load([
            'user',
            'attendanceRecord',
            'applicationBreaks',
        ]);

        $application->approval_status = $application->status === 'approved'
            ? '承認済み'
            : '承認待ち';

        $application->new_date = Carbon::parse(
            $application->attendanceRecord->date
        );

        $application->new_clock_in = $this->formatTime(
            $application->clock_in
        );

        $application->new_clock_out = $this->formatTime(
            $application->clock_out
        );

        $application->setRelation(
            'proposalBreaks',
            $application->applicationBreaks
        );

        return view('admin.admin-application-detail', [
            'application' => $application,
            'user' => $application->user,
        ]);
    }

    /**
     * 修正申請を承認して勤怠情報へ反映する。
     *
     * @param  int  $attendance_correct_request_id  修正申請ID
     * @return RedirectResponse 承認画面へのリダイレクト
     */
    public function approve(
        int $attendance_correct_request_id
    ): RedirectResponse {
        $application = Application::findOrFail(
            $attendance_correct_request_id
        );

        if ($application->status === 'approved') {
            return redirect(
                '/stamp_correction_request/approve/'.$application->id
            );
        }

        $application->load([
            'attendanceRecord.breaks',
            'applicationBreaks',
        ]);

        DB::transaction(function () use ($application): void {
            $attendanceRecord = $application->attendanceRecord;

            $attendanceRecord->update([
                'clock_in' => $application->clock_in,
                'clock_out' => $application->clock_out,
                'comment' => $application->comment,
            ]);

            $attendanceRecord->breaks()->delete();

            foreach ($application->applicationBreaks as $break) {
                $attendanceRecord->breaks()->create([
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ]);
            }

            $application->update([
                'status' => 'approved',
            ]);
        });

        return redirect(
            '/stamp_correction_request/approve/'.$application->id
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
