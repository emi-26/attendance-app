<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            FortifyLoginRequest::class,
            function ($app) {
                if (request()->is('admin/login')) {
                    return $app->make(AdminLoginRequest::class);
                }

                return $app->make(LoginRequest::class);
            }
        );

        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class
        );
    }

    public function boot(): void
    {
        Fortify::registerView(function () {
            return view('user.register');
        });

        Fortify::loginView(function () {
            return view('user.user-login');
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if ($request->is('admin/login')) {
                return $user->admin_status ? $user : null;
            }

            return ! $user->admin_status ? $user : null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
