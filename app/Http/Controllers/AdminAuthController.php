<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.admin-login');
    }
}
