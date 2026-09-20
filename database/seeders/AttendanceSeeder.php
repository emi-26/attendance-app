<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * 勤怠データを登録する。
     */
    public function run(): void
    {
        $service = new AttendanceSeedService;

        $user1 = User::where('email', 'user1@example.com')
            ->firstOrFail();

        $user2 = User::where('email', 'user2@example.com')
            ->firstOrFail();

        $user3 = User::where('email', 'user3@example.com')
            ->firstOrFail();

        $service->seedMainUser($user1);
        $service->seedRecentWeekdays($user2);
        $service->seedRecentWeekdays($user3);
    }
}
