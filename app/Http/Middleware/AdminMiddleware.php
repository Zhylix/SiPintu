<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->hasPermission('access-admin')) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses ke Admin Panel Gateway.');
        }

        return $next($request);
    }
}
