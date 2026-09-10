<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStampCorrectionRequestController extends Controller
{
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

    public function show(Application $application): View
    {
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

    public function approve(
        Application $application
    ): RedirectResponse {
        if ($application->status === 'approved') {
            return redirect(
                '/stamp_correction_request/approve/'.$application->id
            );
        }

        $application->load([
            'attendanceRecord.breaks',
            'applicationBreaks',
        ]);

        DB::transaction(function () use ($application) {
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

    private function formatTime(?string $time): string
    {
        return $time
            ? Carbon::parse($time)->format('H:i')
            : '';
    }
}
