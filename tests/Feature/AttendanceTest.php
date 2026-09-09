<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_date_and_time_are_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 10, 30, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年9月9日(水)');
        $response->assertSee('10:30');
    }

    public function test_status_is_off_duty_when_no_attendance_exists(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
        $response->assertSee('出勤');
    }

    public function test_status_is_working_after_clock_in(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertSee('退勤');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
        ]);
    }

    public function test_clock_in_can_only_be_recorded_once_per_day(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 10, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $this->assertDatabaseCount('attendance_records', 1);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
        ]);
    }

    public function test_status_is_on_break_after_break_in(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 12, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');

        $attendanceRecord = AttendanceRecord::where(
            'user_id',
            $user->id
        )->first();

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);
    }

    public function test_break_out_changes_status_back_to_working(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 12, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 13, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        $this->assertDatabaseHas('breaks', [
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }

    public function test_break_can_be_recorded_multiple_times_per_day(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 12, 0, 0, 'Asia/Tokyo')
        );
        $this->post('/attendance', ['action' => 'break_in']);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 13, 0, 0, 'Asia/Tokyo')
        );
        $this->post('/attendance', ['action' => 'break_out']);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 15, 0, 0, 'Asia/Tokyo')
        );
        $this->post('/attendance', ['action' => 'break_in']);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 15, 30, 0, 'Asia/Tokyo')
        );
        $this->post('/attendance', ['action' => 'break_out']);

        $attendanceRecord = AttendanceRecord::where(
            'user_id',
            $user->id
        )->first();

        $this->assertDatabaseCount('breaks', 2);

        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '15:00:00',
            'break_out' => '15:30:00',
        ]);
    }

    public function test_status_is_finished_after_clock_out(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 18, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    public function test_clock_out_can_only_be_recorded_once_per_day(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 9, 0, 0, 'Asia/Tokyo')
        );

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 18, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'clock_out',
        ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 9, 19, 0, 0, 'Asia/Tokyo')
        );

        $this->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_out' => '18:00:00',
        ]);
    }
}
