<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 管理者ユーザーのみ後続処理へ進める。
     *
     * @param  Request  $request  リクエスト
     * @param  Closure  $next  次の処理
     * @return Response レスポンス
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! $request->user()?->admin_status) {
            abort(403);
        }

        return $next($request);
    }
}
