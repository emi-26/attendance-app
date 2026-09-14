<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_general_users_are_displayed(): void
    {
        $admin = $this->createAdmin([
            'name' => '管理者',
            'email' => 'admin@example.com',
        ]);

        $this->createUser([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
        ]);

        $this->createUser([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('一般ユーザー1');
        $response->assertSee('user1@example.com');
        $response->assertSee('一般ユーザー2');
        $response->assertSee('user2@example.com');
        $response->assertDontSee('admin@example.com');
    }

    public function test_staff_detail_link_opens_monthly_attendance(): void
    {
        $admin = $this->createAdmin();

        $user = $this->createUser([
            'name' => '対象スタッフ',
            'email' => 'staff@example.com',
        ]);

        $listResponse = $this->actingAs($admin)
            ->get('/admin/staff/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            '/admin/attendance/staff/'.$user->id,
            false
        );

        $detailResponse = $this->get(
            '/admin/attendance/staff/'.$user->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('対象スタッフ');
    }

    public function test_selected_staff_attendance_is_displayed_correctly(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = $this->createAdmin();

        $user = $this->createUser([
            'name' => '対象スタッフ',
        ]);

        $otherUser = $this->createUser();

        $record = $this->createAttendanceRecord(
            $user,
            [
                'date' => '2026-09-05',
            ]
        );

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->createAttendanceRecord(
            $otherUser,
            [
                'date' => '2026-09-06',
                'clock_in' => '07:00:00',
                'clock_out' => '16:00:00',
            ]
        );

        $response = $this->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'.
                $user->id.
                '?date=2026-09'
            );

        $response->assertStatus(200);
        $response->assertSee('対象スタッフ');
        $response->assertSee('2026/09');
        $response->assertSee('09/05(土)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertDontSee('07:00');
        $response->assertDontSee('16:00');
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
