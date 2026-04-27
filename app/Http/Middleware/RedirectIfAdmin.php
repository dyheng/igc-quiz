<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(config('quiz.admin_session_key'))) {
            return redirect()->route('admin.quizzes.index');
        }

        return $next($request);
    }
}
