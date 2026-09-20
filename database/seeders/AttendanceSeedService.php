<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeedService
{
    /**
     * メインユーザーの6ヶ月分の勤怠を登録する。
     *
     * @param  User  $user  対象ユーザー
     */
    public function seedMainUser(User $user): void
    {
        $this->seedPreviousMonths($user);

        $dates = $this->getCurrentMonthWeekdays();

        $this->seedNormalDays($user, $dates);
        $this->seedSpecialDays($user, $dates);
    }

    /**
     * 直近5平日分の通常勤務を登録する。
     *
     * @param  User  $user  対象ユーザー
     */
    public function seedRecentWeekdays(User $user): void
    {
        $date = now()->subDays(7);
        $count = 0;

        while ($count < 5) {
            if ($date->isWeekday()) {
                $this->createAttendance(
                    $user,
                    $date->copy(),
                    '09:00:00',
                    '18:00:00'
                );

                $count++;
            }

            $date->addDay();
        }
    }

    /**
     * 過去5ヶ月の通常勤務を登録する。
     *
     * @param  User  $user  対象ユーザー
     */
    private function seedPreviousMonths(User $user): void
    {
        for ($month = 5; $month >= 1; $month--) {
            $date = now()
                ->subMonths($month)
                ->startOfMonth();

            $count = 0;

            while ($count < 15) {
                if ($date->isWeekday()) {
                    $this->createAttendance(
                        $user,
                        $date->copy(),
                        '09:00:00',
                        '18:00:00'
                    );

                    $count++;
                }

                $date->addDay();
            }
        }
    }

    /**
     * 当月の平日17日分を取得する。
     *
     * @return array<int, Carbon> 平日の一覧
     */
    private function getCurrentMonthWeekdays(): array
    {
        $dates = [];
        $date = now()->startOfMonth();

        while (count($dates) < 17) {
            if ($date->isWeekday()) {
                $dates[] = $date->copy();
            }

            $date->addDay();
        }

        return $dates;
    }

    /**
     * 当月の通常勤務10日分を登録する。
     *
     * @param  User  $user  対象ユーザー
     * @param  array<int, Carbon>  $dates  勤務日
     */
    private function seedNormalDays(
        User $user,
        array $dates
    ): void {
        for ($i = 0; $i < 10; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:00:00',
                '18:00:00'
            );
        }
    }

    /**
     * 当月の特殊勤務を登録する。
     *
     * @param  User  $user  対象ユーザー
     * @param  array<int, Carbon>  $dates  勤務日
     */
    private function seedSpecialDays(
        User $user,
        array $dates
    ): void {
        for ($i = 10; $i < 13; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:00:00',
                '20:00:00'
            );
        }

        for ($i = 13; $i < 15; $i++) {
            $this->createAttendance(
                $user,
                $dates[$i],
                '09:30:00',
                '18:00:00'
            );
        }

        $this->createAttendance(
            $user,
            $dates[15],
            '09:00:00',
            '17:00:00'
        );

        $this->createAttendance(
            $user,
            $dates[16],
            '08:00:00',
            '21:00:00'
        );
    }

    /**
     * 勤怠と休憩を登録する。
     *
     * @param  User  $user  対象ユーザー
     * @param  Carbon  $date  勤務日
     * @param  string  $clockIn  出勤時刻
     * @param  string  $clockOut  退勤時刻
     */
    private function createAttendance(
        User $user,
        Carbon $date,
        string $clockIn,
        string $clockOut
    ): void {
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => '通常勤務',
        ]);

        $attendance->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}
