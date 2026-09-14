<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_own_attendance_records_are_displayed(): void
    {
        $this->setTestNow();

        $user = $this->createUser();
        $otherUser = $this->createUser();

        $record = $this->createAttendanceRecord(
            $user,
            ['date' => '2026-09-01']
        );

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->createAttendanceRecord(
            $user,
            [
                'date' => '2026-09-02',
                'clock_in' => '08:30:00',
                'clock_out' => '17:30:00',
            ]
        );

        $this->createAttendanceRecord(
            $otherUser,
            [
                'date' => '2026-09-03',
                'clock_in' => '07:00:00',
                'clock_out' => '16:00:00',
            ]
        );

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $response->assertStatus(200);
        $response->assertSee([
            '09/01(火)',
            '09:00',
            '18:00',
            '1:00',
            '8:00',
            '09/02(水)',
            '08:30',
            '17:30',
        ]);
        $response->assertDontSee([
            '07:00',
            '16:00',
        ]);
    }

    public function test_current_month_is_displayed(): void
    {
        $this->setTestNow();

        $response = $this->actingAs($this->createUser())
            ->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/09');
    }

    public function test_previous_month_information_is_displayed(): void
    {
        $this->setTestNow();

        [$user] = $this->createUserWithAttendance(
            [],
            [
                'date' => '2026-08-15',
                'clock_in' => '09:15:00',
                'clock_out' => '18:15:00',
            ]
        );

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertSee('?date=2026-08', false);

        $response = $this->get(
            '/attendance/list?date=2026-08'
        );

        $response->assertStatus(200);
        $response->assertSee([
            '2026/08',
            '08/15(土)',
            '09:15',
            '18:15',
        ]);
    }

    public function test_next_month_information_is_displayed(): void
    {
        $this->setTestNow();

        [$user] = $this->createUserWithAttendance(
            [],
            [
                'date' => '2026-10-15',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
            ]
        );

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertSee('?date=2026-10', false);

        $response = $this->get(
            '/attendance/list?date=2026-10'
        );

        $response->assertStatus(200);
        $response->assertSee([
            '2026/10',
            '10/15(木)',
            '10:00',
            '19:00',
        ]);
    }

    public function test_detail_link_opens_selected_attendance_detail(): void
    {
        [$user, $record] = $this->createUserWithAttendance(
            ['name' => 'テストユーザー']
        );

        $listResponse = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            '/attendance/'.$record->id,
            false
        );

        $redirectResponse = $this->get(
            '/attendance/'.$record->id
        );

        $redirectResponse->assertRedirect(
            '/attendance/detail/'.$record->id
        );

        $detailResponse = $this->get(
            '/attendance/detail/'.$record->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee([
            'テストユーザー',
            '2026年',
            '9月10日',
            '09:00',
            '18:00',
        ]);
    }

    public function test_other_users_attendance_detail_cannot_be_viewed(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $record = $this->createAttendanceRecord($otherUser);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/'.$record->id);

        $response->assertForbidden();
    }
}
