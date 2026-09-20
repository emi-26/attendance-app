<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_report_displays_six_month_summary_and_anomalies(): void
    {
        $this->setReportTestTime();

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->createRecordWithBreak(
            $user,
            '2026-09-10',
            '09:30:00',
            '17:30:00',
            '通常勤務'
        );

        $this->createRecordWithBreak(
            $user,
            '2026-09-11',
            '08:00:00',
            '20:30:00',
            '長時間勤務'
        );

        $this->createRecordWithBreak(
            $user,
            '2026-08-15',
            '09:00:00',
            '18:00:00',
            '通常勤務'
        );

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);
        $response->assertSee('マイ勤怠レポート');

        $this->assertReportSummary($response);
        $this->assertMonthlyTrend($response);

        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 1,
            'long_work_count' => 1,
        ]);
    }

    public function test_report_does_not_include_records_older_than_six_months(): void
    {
        $this->setReportTestTime();

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-03-31',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '対象外',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);
    }

    public function test_unverified_user_cannot_view_report(): void
    {
        $user = User::factory()->unverified()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertRedirect('/email/verify');
    }

    private function setReportTestTime(): void
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
    }

    private function createRecordWithBreak(
        User $user,
        string $date,
        string $clockIn,
        string $clockOut,
        string $comment
    ): AttendanceRecord {
        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $record->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return $record;
    }

    private function assertReportSummary($response): void
    {
        $response->assertViewHas(
            'summary',
            function (array $summary): bool {
                return $summary['total_work_minutes'] === 1590
                    && $summary['total_overtime_minutes'] === 210
                    && $summary['avg_work_minutes'] === 530;
            }
        );
    }

    private function assertMonthlyTrend($response): void
    {
        $response->assertViewHas(
            'monthlyTrend',
            function ($monthlyTrend): bool {
                $august = $monthlyTrend->firstWhere(
                    'month',
                    '2026/08'
                );

                $september = $monthlyTrend->firstWhere(
                    'month',
                    '2026/09'
                );

                return $august['work_minutes'] === 480
                    && $august['overtime_minutes'] === 0
                    && $september['work_minutes'] === 1110
                    && $september['overtime_minutes'] === 210;
            }
        );
    }
}
