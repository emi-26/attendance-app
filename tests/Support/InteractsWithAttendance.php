<?php

namespace Tests\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;

trait InteractsWithAttendance
{
    protected function setTestNow(): void
    {
        $this->setTestTime(10);
    }

    protected function setTestTime(
        int $hour,
        int $minute = 0
    ): void {
        Carbon::setTestNow(
            Carbon::create(
                2026,
                9,
                10,
                $hour,
                $minute,
                0,
                'Asia/Tokyo'
            )
        );
    }

    protected function postAttendanceAction(
        string $action
    ): TestResponse {
        return $this->post('/attendance', [
            'action' => $action,
        ]);
    }

    protected function startWork(): void
    {
        $user = $this->createUser();

        $this->startWorkAt($user);
    }

    protected function startWorkAt(
        User $user,
        int $hour = 9,
        int $minute = 0
    ): void {
        $this->setTestTime($hour, $minute);
        $this->actingAs($user);
        $this->postAttendanceAction('clock_in');
    }

    protected function startBreakAt(
        int $hour,
        int $minute = 0
    ): void {
        $this->setTestTime($hour, $minute);
        $this->postAttendanceAction('break_in');
    }

    protected function endBreakAt(
        int $hour,
        int $minute = 0
    ): void {
        $this->setTestTime($hour, $minute);
        $this->postAttendanceAction('break_out');
    }

    protected function endWorkAt(
        int $hour,
        int $minute = 0
    ): void {
        $this->setTestTime($hour, $minute);
        $this->postAttendanceAction('clock_out');
    }
}
