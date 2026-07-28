<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // Normalize aliases: siswa -> student, guru -> teacher
        $normalizedRoles = array_map(function ($r) {
            return match ($r) {
                'siswa' => 'student',
                'guru' => 'teacher',
                default => $r,
            };
        }, $roles);

        // Check if user has any of the required roles
        foreach ($normalizedRoles as $role) {
            if ($role === 'admin' && $user->isAdmin()) {
                return $next($request);
            }
            if ($role === 'teacher' && $user->isTeacher()) {
                return $next($request);
            }
            if ($role === 'dudi' && $user->isDudi()) {
                return $next($request);
            }
            if ($role === 'student' && $user->isStudent()) {
                return $next($request);
            }
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // Access denied: Role mismatch
        $roleNamesMap = [
            'admin' => 'Administrator',
            'teacher' => 'Guru / Pendidik',
            'dudi' => 'Mitra DUDI',
            'student' => 'Siswa',
        ];

        $requiredRoleNames = array_map(fn($r) => $roleNamesMap[$r] ?? ucfirst($r), $normalizedRoles);
        $roleListStr = implode(' atau ', $requiredRoleNames);

        abort(403, "Akses Ditolak: Halaman ini khusus untuk pengguna dengan role {$roleListStr}. Akun Anda saat ini terdaftar sebagai {$user->getUserTypeName()}.");
    }
}
