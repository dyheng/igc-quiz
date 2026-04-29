<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proteksi endpoint load test dengan secret key.
 * Set LOADTEST_SECRET di .env untuk mengaktifkan endpoint.
 * Kosongkan / hapus untuk menonaktifkan (default di production).
 */
class EnsureLoadTestSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.loadtest_secret');

        if (empty($secret)) {
            abort(403, 'Load test endpoint tidak aktif. Set LOADTEST_SECRET di .env untuk mengaktifkan.');
        }

        if ($request->header('X-Loadtest-Secret') !== $secret) {
            abort(403, 'X-Loadtest-Secret header tidak valid.');
        }

        return $next($request);
    }
}
