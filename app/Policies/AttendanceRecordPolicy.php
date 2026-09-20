<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 管理者にはすべての操作を許可する。
     *
     * @param  User  $user  操作するユーザー
     * @return bool|null 管理者ならtrue、それ以外は個別判定へ進む
     */
    public function before(User $user): ?bool
    {
        if ($user->admin_status) {
            return true;
        }

        return null;
    }

    /**
     * 勤怠記録を更新できるか判定する。
     *
     * @param  User  $user  操作するユーザー
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠記録
     * @return bool 本人の勤怠記録ならtrue
     */
    public function update(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠記録を削除できるか判定する。
     *
     * @param  User  $user  操作するユーザー
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠記録
     * @return bool 本人の勤怠記録ならtrue
     */
    public function delete(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->id === $attendanceRecord->user_id;
    }
}
