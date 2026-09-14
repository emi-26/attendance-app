<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class StampCorrectionRequestTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    public function test_correction_application_is_displayed_for_admin(): void
    {
        [$user, $record] = $this->createTestAttendance();

        $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
                'new_clock_in' => '08:30',
                'new_clock_out' => '17:30',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '打刻修正',
            ]);

        $admin = $this->createAdmin();

        $listResponse = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee([
            $user->name,
            '打刻修正',
            '承認待ち',
        ]);

        $application = $record->applications()->first();

        $detailResponse = $this->get(
            '/stamp_correction_request/approve/'.$application->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee([
            $user->name,
            '08:30',
            '17:30',
            '12:00',
            '13:00',
            '打刻修正',
        ]);
    }

    public function test_own_pending_applications_are_displayed(): void
    {
        [$user, $record1] = $this->createTestAttendance();

        $record2 = $this->createAttendanceRecord(
            $user,
            ['date' => '2026-09-09']
        );

        $this->createTestApplication(
            $record1,
            $user,
            ['comment' => '申請その1']
        );

        $this->createTestApplication(
            $record2,
            $user,
            [
                'clock_in' => '09:30:00',
                'clock_out' => '18:30:00',
                'comment' => '申請その2',
            ]
        );

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee([
            '申請その1',
            '申請その2',
            '承認待ち',
        ]);
    }

    public function test_approved_applications_are_displayed(): void
    {
        [$user, $record] = $this->createTestAttendance();

        $this->createTestApplication(
            $record,
            $user,
            [
                'comment' => '承認された申請',
                'status' => 'approved',
            ]
        );

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee([
            '承認された申請',
            '承認済み',
        ]);
    }

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
