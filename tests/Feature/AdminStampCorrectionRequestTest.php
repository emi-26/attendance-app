<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pending_and_approved_applications_are_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => '申請ユーザー1',
            'admin_status' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => '申請ユーザー2',
            'admin_status' => false,
        ]);

        $record1 = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $record2 = AttendanceRecord::create([
            'user_id' => $user2->id,
            'date' => '2026-09-10',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $record1->applications()->create([
            'user_id' => $user1->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '未承認申請',
            'status' => 'pending',
        ]);

        $record2->applications()->create([
            'user_id' => $user2->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'comment' => '承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('申請ユーザー1');
        $response->assertSee('申請ユーザー2');
        $response->assertSee('未承認申請');
        $response->assertSee('承認済み申請');
        $response->assertSee('承認待ち');
        $response->assertSee('承認済み');
    }

    public function test_application_detail_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '申請ユーザー',
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $application = $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '時刻修正',
            'status' => 'pending',
        ]);

        $application->applicationBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(
                '/stamp_correction_request/approve/'.
                $application->id
            );

        $response->assertStatus(200);
        $response->assertSee('申請ユーザー');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
        $response->assertSee('12:00');
        $response->assertSee('12:45');
        $response->assertSee('時刻修正');
    }

    public function test_admin_can_approve_correction_application(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前',
        ]);

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $application = $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '承認後の内容',
            'status' => 'pending',
        ]);

        $application->applicationBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $response = $this->actingAs($admin)
            ->post(
                '/stamp_correction_request/approve/'.
                $application->id
            );

        $response->assertRedirect(
            '/stamp_correction_request/approve/'.
            $application->id
        );

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '承認後の内容',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $record->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $userListResponse = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $userListResponse->assertStatus(200);
        $userListResponse->assertSee('承認済み');
        $userListResponse->assertSee('承認後の内容');
    }
}
