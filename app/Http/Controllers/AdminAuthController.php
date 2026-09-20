<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * 管理者ログイン画面を表示する。
     *
     * @return View 管理者ログイン画面
     */
    public function showLoginForm(): View
    {
        return view('admin.admin-login');
    }
}
