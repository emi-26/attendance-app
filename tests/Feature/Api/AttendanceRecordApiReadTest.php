<?php

namespace Tests\Feature\Api;

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
            ->assertJsonPath('data.0.date', '2026-09-10');
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

        $response = $this->getJson(
            '/api/v1/attendance-records/'.$attendanceRecord->id
        );

        $response->assertStatus(200)
            ->assertJsonPath(
                'data.id',
                $attendanceRecord->id
            )
            ->assertJsonPath(
                'data.user.id',
                $user->id
            );
    }

    public function test_missing_attendance_record_returns_expected_error(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/999999'
        );

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_attendance_records_can_be_filtered_by_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        AttendanceRecord::create([
            'user_id' => $firstUser->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $secondUser->id,
            'date' => '2026-09-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?user_id='.$firstUser->id
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.user.id',
                $firstUser->id
            );
    }

    public function test_attendance_records_can_be_filtered_by_month(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?month=2026-09'
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.date',
                '2026-09-10'
            );
    }
}
