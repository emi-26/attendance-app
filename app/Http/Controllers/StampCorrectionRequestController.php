<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
    /**
     * 一般ユーザーの申請一覧を表示する。
     *
     * @param  Request  $request  認証済みユーザーのリクエスト
     * @return View 管理者または一般ユーザーの申請一覧画面
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->admin_status === true) {
            return app(AdminStampCorrectionRequestController::class)
                ->index();
        }

        $applications = $user
            ->applications()
            ->with('attendanceRecord')
            ->latest()
            ->get();

        $formattedApplications = $applications
            ->map(function (Application $application) {
                return [
                    'id' => $application->id,
                    'approval_status' => $application->status === 'approved'
                        ? '承認済み'
                        : '承認待ち',
                    'date' => Carbon::parse(
                        $application->attendanceRecord->date
                    )->format('Y/m/d'),
                    'comment' => $application->comment,
                    'application_date' => Carbon::parse(
                        $application->created_at
                    )->format('Y/m/d'),
                ];
            });

        return view('user.user-application-list', [
            'user' => $user,
            'formattedApplications' => $formattedApplications,
        ]);
    }

    /**
     * 選択した申請に対応する勤怠詳細画面へ遷移する。
     *
     * @param  Request  $request  認証済みユーザーのリクエスト
     * @param  Application  $application  対象の申請
     * @return RedirectResponse 勤怠詳細画面へのリダイレクト
     */
    public function show(
        Request $request,
        Application $application
    ): RedirectResponse {
        abort_unless(
            $application->user_id === $request->user()->id,
            403
        );

        return redirect(
            '/attendance/detail/'.$application->attendance_record_id
        );
    }
}
