<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AdminStampCorrectionRequestTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    public function test_all_pending_applications_are_displayed(): void
    {
        $admin = $this->createAdmin();

        [$user1, $record1] = $this->createUserWithAttendance(
            [
                'name' => '申請ユーザー1',
            ],
            [
                'date' => '2026-09-09',
            ]
        );

        [$user2, $record2] = $this->createUserWithAttendance(
            [
                'name' => '申請ユーザー2',
            ],
            [
                'date' => '2026-09-10',
            ]
        );

        $record1->applications()->create([
            'user_id' => $user1->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '未承認申請1',
            'status' => 'pending',
        ]);

        $record2->applications()->create([
            'user_id' => $user2->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'comment' => '未承認申請2',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('申請ユーザー1');
        $response->assertSee('申請ユーザー2');
        $response->assertSee('未承認申請1');
        $response->assertSee('未承認申請2');
        $response->assertSee('承認待ち');
    }

    public function test_all_approved_applications_are_displayed(): void
    {
        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            [
                'name' => '承認済みユーザー',
            ],
            [
                'date' => '2026-09-10',
            ]
        );

        $record->applications()->create([
            'user_id' => $user->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認済みユーザー');
        $response->assertSee('承認済み申請');
        $response->assertSee('承認済み');
    }

    public function test_application_detail_is_displayed(): void
    {
        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            [
                'name' => '申請ユーザー',
            ],
            [
                'date' => '2026-09-10',
            ]
        );

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
        $response->assertSee('2026年');
        $response->assertSee('9月10日');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
        $response->assertSee('12:00');
        $response->assertSee('12:45');
        $response->assertSee('時刻修正');
        $response->assertSee('承認');
    }
}
