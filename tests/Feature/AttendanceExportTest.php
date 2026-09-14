<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_staff_attendance_as_csv(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->post('/export', [
                'user_id' => $user->id,
                'year_month' => '2026-09',
            ]);

        $response->assertStatus(200);

        $response->assertHeader(
            'content-type',
            'text/csv; charset=UTF-8'
        );

        $this->assertStringContainsString(
            '2026-09.csv',
            $response->headers->get('content-disposition')
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            '日付,出勤,退勤,休憩,合計',
            $content
        );

        $this->assertStringContainsString(
            '2026/09/10,09:00,18:00,01:00,08:00',
            $content
        );
    }

    public function test_general_user_cannot_export_csv(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->post('/export', [
                'user_id' => $targetUser->id,
                'year_month' => '2026-09',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_user_cannot_be_export_target(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetAdmin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post('/export', [
                'user_id' => $targetAdmin->id,
                'year_month' => '2026-09',
            ]);

        $response->assertStatus(404);
    }
}
