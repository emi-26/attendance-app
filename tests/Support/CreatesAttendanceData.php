<?php

namespace Tests\Support;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;

trait CreatesAttendanceData
{
    use InteractsWithAttendance;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function createAdmin(
        array $attributes = []
    ): User {
        return User::factory()->create(array_merge([
            'admin_status' => true,
        ], $attributes));
    }

    protected function createUser(
        array $attributes = []
    ): User {
        return User::factory()->create(array_merge([
            'admin_status' => false,
        ], $attributes));
    }

    protected function createAttendanceRecord(
        User $user,
        array $attributes = []
    ): AttendanceRecord {
        return AttendanceRecord::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '',
        ], $attributes));
    }

    protected function createAttendanceRecordWithBreak(
        User $user,
        array $attendanceAttributes = []
    ): AttendanceRecord {
        $record = $this->createAttendanceRecord(
            $user,
            $attendanceAttributes
        );

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return $record;
    }

    protected function createUserWithAttendance(
        array $userAttributes = [],
        array $attendanceAttributes = []
    ): array {
        $user = $this->createUser($userAttributes);

        $record = $this->createAttendanceRecord(
            $user,
            $attendanceAttributes
        );

        return [$user, $record];
    }

    protected function createWorkingUser(): User
    {
        [$user] = $this->createUserWithAttendance(
            [],
            ['clock_out' => null]
        );

        return $user;
    }

    protected function createUserOnBreak(): User
    {
        $user = $this->createUser();

        $record = $this->createAttendanceRecord(
            $user,
            ['clock_out' => null]
        );

        $record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        return $user;
    }

    protected function createAdminWithAttendance(): array
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $record = $this->createAttendanceRecordWithBreak(
            $user,
            ['comment' => '通常勤務']
        );

        return [$admin, $record];
    }

    protected function postAdminAttendanceUpdate(
        User $admin,
        AttendanceRecord $record,
        array $attributes = []
    ): TestResponse {
        return $this->actingAs($admin)->post(
            '/admin/attendance/'.$record->id,
            array_merge([
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '修正',
            ], $attributes)
        );
    }

    protected function assertAdminAttendanceValidation(
        array $attributes,
        string $field,
        string $message
    ): void {
        [$admin, $record] = $this->createAdminWithAttendance();

        $this->postAdminAttendanceUpdate(
            $admin,
            $record,
            $attributes
        )->assertSessionHasErrors([
            $field => $message,
        ]);
    }
}
