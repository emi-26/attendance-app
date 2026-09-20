<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AdminStaffAttendanceTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_previous_month_link_displays_previous_month(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->createAttendanceRecord(
            $user,
            [
                'date' => '2026-08-15',
                'clock_in' => '09:15:00',
                'clock_out' => '18:15:00',
            ]
        );

        $currentResponse = $this->actingAs($admin)
            ->get('/admin/attendance/staff/'.$user->id);

        $currentResponse->assertStatus(200);
        $currentResponse->assertSee(
            '?date=2026-08',
            false
        );

        $previousResponse = $this->get(
            '/admin/attendance/staff/'.
            $user->id.
            '?date=2026-08'
        );

        $previousResponse->assertStatus(200);
        $previousResponse->assertSee('2026/08');
        $previousResponse->assertSee('08/15(土)');
        $previousResponse->assertSee('09:15');
        $previousResponse->assertSee('18:15');
    }

    public function test_next_month_link_displays_next_month(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = $this->createAdmin();
        $user = $this->createUser();

        $this->createAttendanceRecord(
            $user,
            [
                'date' => '2026-10-15',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
            ]
        );

        $currentResponse = $this->actingAs($admin)
            ->get('/admin/attendance/staff/'.$user->id);

        $currentResponse->assertStatus(200);
        $currentResponse->assertSee(
            '?date=2026-10',
            false
        );

        $nextResponse = $this->get(
            '/admin/attendance/staff/'.
            $user->id.
            '?date=2026-10'
        );

        $nextResponse->assertStatus(200);
        $nextResponse->assertSee('2026/10');
        $nextResponse->assertSee('10/15(木)');
        $nextResponse->assertSee('10:00');
        $nextResponse->assertSee('19:00');
    }

    public function test_detail_link_opens_selected_attendance_detail(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            [
                'name' => '対象スタッフ',
            ],
            [
                'date' => '2026-09-10',
            ]
        );

        $listResponse = $this->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'.
                $user->id.
                '?date=2026-09'
            );

        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            '/attendance/'.$record->id,
            false
        );

        $redirectResponse = $this->get(
            '/attendance/'.$record->id
        );

        $redirectResponse->assertRedirect(
            '/admin/attendance/'.$record->id
        );

        $detailResponse = $this->get(
            '/admin/attendance/'.$record->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('対象スタッフ');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
    }
}
