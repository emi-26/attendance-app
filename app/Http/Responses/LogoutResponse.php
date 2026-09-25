<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * ログアウト後の遷移先を返す。
     *
     * @param  mixed  $request  ログアウトリクエスト
     * @return mixed リダイレクトレスポンス
     */
    public function toResponse($request)
    {
        if ($request->is('admin/logout')) {
            return redirect('/admin/login');
        }

        return redirect('/login');
    }
}
