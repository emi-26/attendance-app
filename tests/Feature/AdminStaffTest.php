<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_general_users_are_displayed(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'admin_status' => true,
        ]);

        User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
            'admin_status' => false,
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

    public function test_staff_monthly_attendance_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '対象スタッフ',
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-06',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/staff/'.$user->id);

        $response->assertStatus(200);
        $response->assertSee('対象スタッフ');
        $response->assertSee('2026/09');
        $response->assertSee('09/05(土)');
        $response->assertSee('09/06(日)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    public function test_previous_month_attendance_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-15',
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'.
                $user->id.
                '?date=2026-08'
            );

        $response->assertStatus(200);
        $response->assertSee('2026/08');
        $response->assertSee('08/15(土)');
        $response->assertSee('09:15');
        $response->assertSee('18:15');
    }

    public function test_next_month_attendance_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-10-15',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(
                '/admin/attendance/staff/'.
                $user->id.
                '?date=2026-10'
            );

        $response->assertStatus(200);
        $response->assertSee('2026/10');
        $response->assertSee('10/15(木)');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }
}
