<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()
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
            'user' => $request->user(),
            'formattedApplications' => $formattedApplications,
        ]);
    }

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
