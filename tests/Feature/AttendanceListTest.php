<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_own_attendance_records_are_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 10, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-02',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $otherUser->id,
            'date' => '2026-09-03',
            'clock_in' => '07:00:00',
            'clock_out' => '16:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $response->assertStatus(200);

        $response->assertSee('09/01(火)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('09/02(水)');
        $response->assertSee('08:30');
        $response->assertSee('17:30');

        $response->assertDontSee('07:00');
        $response->assertDontSee('16:00');
    }

    public function test_current_month_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 10, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/09');

        $response->assertSee('?date=2026-08', false);
        $response->assertSee('?date=2026-10', false);
    }

    public function test_previous_month_attendance_is_displayed(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-15',
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-08');

        $response->assertStatus(200);
        $response->assertSee('2026/08');
        $response->assertSee('08/15(土)');
        $response->assertSee('09:15');
        $response->assertSee('18:15');
    }

    public function test_next_month_attendance_is_displayed(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-10-15',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-10');

        $response->assertStatus(200);
        $response->assertSee('2026/10');
        $response->assertSee('10/15(木)');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_detail_link_opens_attendance_detail(): void
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
            'comment' => '通常勤務',
        ]);

        $listResponse = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $listResponse->assertSee(
            '/attendance/'.$record->id,
            false
        );

        $detailResponse = $this->get(
            '/attendance/'.$record->id
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('テストユーザー');
        $detailResponse->assertSee('2026年');
        $detailResponse->assertSee('9月9日');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
    }

    public function test_other_users_attendance_detail_cannot_be_viewed(): void
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

        $response = $this->actingAs($user)
            ->get('/attendance/'.$record->id);

        $response->assertForbidden();
    }
}
