<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_detail_is_displayed_correctly(): void
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

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/detail/'.$record->id);

        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('2026年');
        $response->assertSee('9月9日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_clock_in_after_clock_out_is_invalid(): void
    {
        [$user, $record] = $this->createAttendanceRecord();

        $response = $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_break_start_after_clock_out_is_invalid(): void
    {
        [$user, $record] = $this->createAttendanceRecord();

        $response = $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
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
        [$user, $record] = $this->createAttendanceRecord();

        $response = $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
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
        [$user, $record] = $this->createAttendanceRecord();

        $response = $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
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

    public function test_correction_application_is_created(): void
    {
        [$user, $record] = $this->createAttendanceRecord();

        $response = $this->actingAs($user)
            ->post('/attendance/'.$record->id, [
                'new_clock_in' => '08:30',
                'new_clock_out' => '17:30',
                'new_break_in' => [
                    '12:00',
                    '15:00',
                ],
                'new_break_out' => [
                    '13:00',
                    '15:15',
                ],
                'comment' => '打刻修正',
            ]);

        $response->assertRedirect('/stamp_correction_request/list');

        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'comment' => '打刻修正',
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('application_breaks', 2);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    private function createAttendanceRecord(): array
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-09',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        return [$user, $record];
    }
}
