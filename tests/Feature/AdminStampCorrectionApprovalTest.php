<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AdminStampCorrectionApprovalTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    public function test_admin_can_approve_application_and_update_attendance(): void
    {
        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            [
                'name' => '一般ユーザー',
            ],
            [
                'date' => '2026-09-10',
                'comment' => '修正前',
            ]
        );

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

        $userResponse = $this->actingAs($user)
            ->get('/attendance/detail/'.$record->id);

        $userResponse->assertStatus(200);
        $userResponse->assertSee('08:30');
        $userResponse->assertSee('17:30');
        $userResponse->assertSee('12:00');
        $userResponse->assertSee('12:45');
        $userResponse->assertSee('承認後の内容');
    }

    public function test_approved_application_is_displayed_as_approved_after_approval(): void
    {
        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            [
                'name' => '一般ユーザー',
            ],
            [
                'date' => '2026-09-10',
            ]
        );

        $application = $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '承認対象',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(
                '/stamp_correction_request/approve/'.
                $application->id
            );

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認対象');
        $response->assertSee('承認済み');
    }
}
