<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class StampCorrectionRequestApprovalTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    public function test_other_users_applications_are_not_displayed(): void
    {
        [$user] = $this->createTestAttendance();

        [$otherUser, $otherRecord] = $this->createUserWithAttendance(
            [],
            ['date' => '2026-09-08']
        );

        $this->createTestApplication(
            $otherRecord,
            $otherUser,
            [
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'comment' => '他人の申請',
            ]
        );

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertDontSee('他人の申請');
    }

    public function test_application_detail_opens_attendance_detail(): void
    {
        [$user, $record] = $this->createTestAttendance();

        $application = $this->createTestApplication(
            $record,
            $user,
            ['comment' => '修正申請']
        );

        $response = $this->actingAs($user)
            ->get('/application/'.$application->id);

        $response->assertRedirect(
            '/attendance/detail/'.$record->id
        );

        $detailResponse = $this->get(
            '/attendance/detail/'.$record->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee([
            '08:30',
            '17:30',
            '修正申請',
        ]);
    }

    public function test_approved_application_moves_to_approved_status(): void
    {
        [$user, $record] = $this->createTestAttendance();

        $application = $this->createTestApplication(
            $record,
            $user,
            ['comment' => '承認対象']
        );

        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(
                '/stamp_correction_request/approve/'.$application->id
            );

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee([
            '承認対象',
            '承認済み',
        ]);
    }

    private function createTestAttendance(): array
    {
        return $this->createUserWithAttendance(
            ['name' => 'テストユーザー'],
            ['comment' => '通常勤務']
        );
    }

    private function createTestApplication(
        AttendanceRecord $record,
        User $user,
        array $attributes = []
    ): Application {
        return $record->applications()->create(array_merge([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '',
            'status' => 'pending',
        ], $attributes));
    }
}
