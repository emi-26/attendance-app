<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_users_attendance_for_selected_day_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
            'admin_status' => false,
        ]);

        $record1 = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $record1->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceRecord::create([
            'user_id' => $user2->id,
            'date' => '2026-09-10',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-10');

        $response->assertStatus(200);
        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_current_date_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 10, 0, 0, 'Asia/Tokyo')
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026');
        $response->assertSee('09');
        $response->assertSee('10');
    }

    public function test_previous_day_attendance_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '前日ユーザー',
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-09');

        $response->assertStatus(200);
        $response->assertSee('前日ユーザー');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    public function test_next_day_attendance_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '翌日ユーザー',
            'admin_status' => false,
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-11',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-11');

        $response->assertStatus(200);
        $response->assertSee('翌日ユーザー');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_selected_attendance_detail_is_displayed(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '詳細ユーザー',
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/'.$record->id);

        $response->assertStatus(200);
        $response->assertSee('詳細ユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    public function test_clock_in_after_clock_out_is_invalid(): void
    {
        [$admin, $record] = $this->createRecord();

        $response = $this->actingAs($admin)
            ->post('/admin/attendance/'.$record->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_break_start_after_clock_out_is_invalid(): void
    {
        [$admin, $record] = $this->createRecord();

        $response = $this->actingAs($admin)
            ->post('/admin/attendance/'.$record->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['19:00'],
                'new_break_out' => ['19:30'],
                'comment' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_break_end_after_clock_out_is_invalid(): void
    {
        [$admin, $record] = $this->createRecord();

        $response = $this->actingAs($admin)
            ->post('/admin/attendance/'.$record->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['17:30'],
                'new_break_out' => ['18:30'],
                'comment' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_comment_is_required(): void
    {
        [$admin, $record] = $this->createRecord();

        $response = $this->actingAs($admin)
            ->post('/admin/attendance/'.$record->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    public function test_admin_can_directly_update_attendance(): void
    {
        [$admin, $record] = $this->createRecord();

        $response = $this->actingAs($admin)
            ->post('/admin/attendance/'.$record->id, [
                'new_clock_in' => '08:30',
                'new_clock_out' => '17:30',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['12:45'],
                'comment' => '管理者修正',
            ]);

        $response->assertRedirect(
            '/admin/attendance/'.$record->id
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '管理者修正',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $record->id,
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);
    }

    private function createRecord(): array
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
            'comment' => '通常勤務',
        ]);

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return [$admin, $record];
    }
}
