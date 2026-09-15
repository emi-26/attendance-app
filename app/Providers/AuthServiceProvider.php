<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Policies\AttendanceRecordPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AttendanceRecord::class => AttendanceRecordPolicy::class,
    ];

    public function boot(): void {}
}
