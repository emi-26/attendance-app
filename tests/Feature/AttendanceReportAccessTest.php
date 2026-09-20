<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_report(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_report_displays_zero_values_when_user_has_no_attendance_records(): void
    {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                9,
                15,
                12,
                0,
                0,
                'Asia/Tokyo'
            )
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas(
            'summary',
            [
                'total_work_minutes' => 0,
                'total_overtime_minutes' => 0,
                'avg_work_minutes' => 0,
            ]
        );

        $response->assertViewHas(
            'monthlyTrend',
            function ($monthlyTrend): bool {
                return $monthlyTrend->count() === 6
                    && $monthlyTrend->every(
                        fn (array $month): bool => $month['work_minutes'] === 0
                            && $month['overtime_minutes'] === 0
                    );
            }
        );

        $response->assertViewHas(
            'anomalies',
            [
                'late_count' => 0,
                'early_leave_count' => 0,
                'long_work_count' => 0,
            ]
        );

        Carbon::setTestNow();
    }
}
