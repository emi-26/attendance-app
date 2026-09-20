<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * ログイン後の遷移先をユーザー種別に応じて返す。
     *
     * @param  mixed  $request  ログインリクエスト
     * @return mixed リダイレクトレスポンス
     */
    public function toResponse($request)
    {
        if ($request->user()->admin_status) {
            return redirect('/admin/attendance/list');
        }

        return redirect('/attendance');
    }
}
