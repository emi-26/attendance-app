<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordApiReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_records_can_be_listed(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_attendance_record_detail_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $break = AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $application = Application::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:00:00',
            'comment' => '修正申請',
            'status' => 'pending',
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records/'.$attendanceRecord->id
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $attendanceRecord->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.breaks.0.id', $break->id)
            ->assertJsonPath(
                'data.applications.0.id',
                $application->id
            );
    }

    public function test_missing_attendance_record_returns_expected_error(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
