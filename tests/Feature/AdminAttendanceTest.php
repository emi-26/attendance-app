<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceData;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use CreatesAttendanceData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_all_users_attendance_for_selected_day_is_displayed(): void
    {
        $this->setTestNow();
        $admin = $this->createAdmin();

        [$user1, $record1] = $this->createUserWithAttendance([
            'name' => 'ユーザー1',
        ]);

        $record1->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->createUserWithAttendance(
            ['name' => 'ユーザー2'],
            ['clock_in' => '10:00:00', 'clock_out' => '19:00:00']
        );

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-10');

        $response->assertStatus(200);
        $response->assertSee([
            'ユーザー1', 'ユーザー2', '09:00', '18:00', '10:00', '19:00',
        ]);
    }

    public function test_current_date_is_displayed(): void
    {
        $this->setTestNow();

        $response = $this->actingAs($this->createAdmin())
            ->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(['2026', '09', '10']);
    }

    public function test_previous_day_attendance_is_displayed(): void
    {
        $this->setTestNow();
        $admin = $this->createAdmin();

        $this->createUserWithAttendance(
            ['name' => '前日ユーザー'],
            [
                'date' => '2026-09-09',
                'clock_in' => '08:30:00',
                'clock_out' => '17:30:00',
            ]
        );

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertSee('?date=2026-09-09', false);

        $response = $this->get('/admin/attendance/list?date=2026-09-09');

        $response->assertStatus(200);
        $response->assertSee(['前日ユーザー', '08:30', '17:30']);
    }

    public function test_next_day_attendance_is_displayed(): void
    {
        $this->setTestNow();
        $admin = $this->createAdmin();

        $this->createUserWithAttendance(
            ['name' => '翌日ユーザー'],
            [
                'date' => '2026-09-11',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
            ]
        );

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertSee('?date=2026-09-11', false);

        $response = $this->get('/admin/attendance/list?date=2026-09-11');

        $response->assertStatus(200);
        $response->assertSee(['翌日ユーザー', '10:00', '19:00']);
    }

    public function test_selected_attendance_detail_is_displayed(): void
    {
        $admin = $this->createAdmin();

        [$user, $record] = $this->createUserWithAttendance(
            ['name' => '詳細ユーザー'],
            ['comment' => '通常勤務']
        );

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/'.$record->id);

        $response->assertStatus(200);
        $response->assertSee([
            '詳細ユーザー', '09:00', '18:00', '12:00', '13:00', '通常勤務',
        ]);
    }

    public function test_clock_in_after_clock_out_is_invalid(): void
    {
        $this->assertAdminAttendanceValidation(
            ['new_clock_in' => '19:00'],
            'new_clock_in',
            '出勤時間もしくは退勤時間が不適切な値です'
        );
    }

    public function test_break_start_after_clock_out_is_invalid(): void
    {
        $this->assertAdminAttendanceValidation(
            ['new_break_in' => ['19:00'], 'new_break_out' => ['19:30']],
            'new_break_in.0',
            '休憩時間が不適切な値です'
        );
    }

    public function test_break_end_after_clock_out_is_invalid(): void
    {
        $this->assertAdminAttendanceValidation(
            ['new_break_in' => ['17:30'], 'new_break_out' => ['18:30']],
            'new_break_out.0',
            '休憩時間もしくは退勤時間が不適切な値です'
        );
    }

    public function test_comment_is_required(): void
    {
        $this->assertAdminAttendanceValidation(
            ['comment' => ''],
            'comment',
            '備考を記入してください'
        );
    }

    public function test_admin_can_directly_update_attendance(): void
    {
        [$admin, $record] = $this->createAdminWithAttendance();

        $response = $this->postAdminAttendanceUpdate($admin, $record, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'new_break_out' => ['12:45'],
            'comment' => '管理者修正',
        ]);

        $response->assertRedirect('/admin/attendance/'.$record->id);

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
}
