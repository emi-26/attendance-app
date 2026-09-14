<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();
        $user3 = User::where('email', 'user3@example.com')->firstOrFail();

        $createAttendance = function (
            User $user,
            Carbon $date,
            string $clockIn,
            string $clockOut,
            string $breakIn = '12:00:00',
            string $breakOut = '13:00:00'
        ): void {
            $attendance = AttendanceRecord::create([
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'comment' => '通常勤務',
            ]);

            $attendance->breaks()->create([
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        };

        // user1：過去5ヶ月、それぞれ平日15日分の通常勤務
        for ($month = 5; $month >= 1; $month--) {
            $date = now()->subMonths($month)->startOfMonth();
            $count = 0;

            while ($count < 15) {
                if ($date->isWeekday()) {
                    $createAttendance(
                        $user1,
                        $date->copy(),
                        '09:00:00',
                        '18:00:00'
                    );

                    $count++;
                }

                $date->addDay();
            }
        }

        // user1：当月17日分
        $currentMonthDates = [];
        $date = now()->startOfMonth();

        while (count($currentMonthDates) < 17) {
            if ($date->isWeekday()) {
                $currentMonthDates[] = $date->copy();
            }

            $date->addDay();
        }

        // 通常勤務 10日
        for ($i = 0; $i < 10; $i++) {
            $createAttendance(
                $user1,
                $currentMonthDates[$i],
                '09:00:00',
                '18:00:00'
            );
        }

        // 残業 3日
        for ($i = 10; $i < 13; $i++) {
            $createAttendance(
                $user1,
                $currentMonthDates[$i],
                '09:00:00',
                '20:00:00'
            );
        }

        // 遅刻 2日
        for ($i = 13; $i < 15; $i++) {
            $createAttendance(
                $user1,
                $currentMonthDates[$i],
                '09:30:00',
                '18:00:00'
            );
        }

        // 早退 1日
        $createAttendance(
            $user1,
            $currentMonthDates[15],
            '09:00:00',
            '17:00:00'
        );

        // 長時間労働 1日
        $createAttendance(
            $user1,
            $currentMonthDates[16],
            '08:00:00',
            '21:00:00'
        );

        // user2：直近5平日分
        $date = now()->subDays(7);
        $count = 0;

        while ($count < 5) {
            if ($date->isWeekday()) {
                $createAttendance(
                    $user2,
                    $date->copy(),
                    '09:00:00',
                    '18:00:00'
                );

                $count++;
            }

            $date->addDay();
        }

        // user3：直近5平日分
        $date = now()->subDays(7);
        $count = 0;

        while ($count < 5) {
            if ($date->isWeekday()) {
                $createAttendance(
                    $user3,
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
