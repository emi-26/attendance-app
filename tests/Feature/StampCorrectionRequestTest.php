<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_own_pending_and_approved_applications_are_displayed(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '承認待ち申請',
            'status' => 'pending',
        ]);

        $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('承認済み');
        $response->assertSee('承認待ち申請');
        $response->assertSee('承認済み申請');
        $response->assertSee('2026/09/09');
    }

    public function test_other_users_applications_are_not_displayed(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $otherUser->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $record->applications()->create([
            'user_id' => $otherUser->id,
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '他人の申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertDontSee('他人の申請');
    }

    public function test_application_detail_opens_attendance_detail(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $application = $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '修正申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/application/'.$application->id);

        $response->assertRedirect(
            '/attendance/detail/'.$record->id
        );
    }
}
