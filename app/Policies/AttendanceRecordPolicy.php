<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function update(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->admin_status
            || $user->id === $attendanceRecord->user_id;
    }

    public function delete(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->admin_status
            || $user->id === $attendanceRecord->user_id;
    }
}
