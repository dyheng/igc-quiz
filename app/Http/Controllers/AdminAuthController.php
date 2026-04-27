<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function logout(Request $request)
    {
        $request->session()->forget(config('quiz.admin_session_key'));
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }
}
