<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    /**
     * 認証ユーザーの過去6ヶ月分の勤怠レポートを表示する。
     *
     * @param  Request  $request  認証済みユーザー情報を含むリクエスト
     * @param  AttendanceReportService  $attendanceReportService  勤怠レポート集計サービス
     * @return View 勤怠レポート画面
     */
    public function index(
        Request $request,
        AttendanceReportService $attendanceReportService
    ): View {
        $endMonth = Carbon::now('Asia/Tokyo')->startOfMonth();
        $startMonth = $endMonth->copy()->subMonths(5);

        $records = AttendanceRecord::with('breaks')
            ->where('user_id', $request->user()->id)
            ->whereBetween('date', [
                $startMonth->toDateString(),
                $endMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        $report = $attendanceReportService->buildReport(
            $startMonth,
            $endMonth,
            $records
        );

        return view('reports.index', $report);
    }
}
