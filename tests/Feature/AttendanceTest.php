<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    public function test_current_date_and_time_are_displayed(): void
    {
        $this->setTestTime(10, 30);

        $response = $this->actingAs($this->createUser())
            ->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(['2026年9月10日(木)', '10:30']);
    }

    public function test_status_is_off_duty(): void
    {
        $response = $this->actingAs($this->createUser())
            ->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_status_is_working(): void
    {
        $this->setTestTime(10);

        $response = $this->actingAs($this->createWorkingUser())
            ->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_status_is_on_break(): void
    {
        $this->setTestTime(12, 30);

        $response = $this->actingAs($this->createUserOnBreak())
            ->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_status_is_finished(): void
    {
        $this->setTestTime(19);

        [$user] = $this->createUserWithAttendance();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('退勤済');
    }

    public function test_clock_in_button_works(): void
    {
        $user = $this->createUser();

        $this->setTestTime(9);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤');

        $this->startWorkAt($user);

        $this->get('/attendance')->assertSee('出勤中');
    }

    public function test_clock_in_can_only_be_done_once_per_day(): void
    {
        $user = $this->createUser();

        $this->startWorkAt($user);
        $this->endWorkAt(18);

        $this->get('/attendance')->assertDontSee(
            '<button class="attendance__button attendance__button--clock-in"',
            false
        );

        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        $user = $this->createUser();

        $this->startWorkAt($user, 9, 15);

        $response = $this->get('/attendance/list?date=2026-09');

        $response->assertSee('09:15');
    }

    public function test_break_in_button_works(): void
    {
        $this->startWork();

        $this->setTestTime(12);

        $this->get('/attendance')->assertSee('休憩入');

        $this->startBreakAt(12);

        $this->get('/attendance')->assertSee([
            '休憩中',
            '休憩戻',
        ]);
    }

    public function test_break_can_be_done_multiple_times_per_day(): void
    {
        $this->startWork();

        $this->startBreakAt(12);
        $this->endBreakAt(13);

        $this->get('/attendance')->assertSee('休憩入');
    }

    public function test_break_out_button_works(): void
    {
        $this->startWork();
        $this->startBreakAt(12);

        $this->setTestTime(13);

        $this->get('/attendance')->assertSee('休憩戻');

        $this->endBreakAt(13);

        $this->get('/attendance')->assertSee('出勤中');
    }

    public function test_break_out_can_be_done_multiple_times_per_day(): void
    {
        $this->startWork();

        $this->startBreakAt(12);
        $this->endBreakAt(13);
        $this->startBreakAt(15);

        $this->get('/attendance')->assertSee('休憩戻');

        $this->endBreakAt(15, 30);

        $this->assertDatabaseCount('breaks', 2);
    }

    public function test_break_time_is_displayed_on_attendance_list(): void
    {
        $this->startWork();

        $this->startBreakAt(12);
        $this->endBreakAt(13);

        $response = $this->get('/attendance/list?date=2026-09');

        $response->assertSee('1:00');
    }

    public function test_clock_out_button_works(): void
    {
        $this->startWork();

        $this->setTestTime(18);

        $this->get('/attendance')->assertSee('退勤');

        $this->endWorkAt(18);

        $this->get('/attendance')->assertSee('退勤済');
    }

    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        $this->startWork();

        $this->endWorkAt(18, 20);

        $response = $this->get('/attendance/list?date=2026-09');

        $response->assertSee('18:20');
    }
}
