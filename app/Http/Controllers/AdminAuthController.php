<?php

namespace App\Http\Controllers;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.admin-login');
    }
}
