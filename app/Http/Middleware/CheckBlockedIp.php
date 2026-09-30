<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    public function __construct(
        protected SecurityService $securityService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Bypass static PWA and asset routes so service workers and icons are never blocked
        if ($this->isBypassableAsset($request)) {
            return $next($request);
        }

        // 2. Bypass Admin unblocking actions so admins are never locked out of recovery
        if ($request->is('monitoring/blocked-ips/*') || $request->is('admin/monitoring/blocked-ips/*')) {
            return $next($request);
        }

        // 3. Bypass already authenticated administrator
        if (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->hasPermission('access-admin'))) {
            return $next($request);
        }

        // 4. Protect active sessions of legitimately logged-in users (do not disrupt active classes)
        if (Auth::check() && ! $request->is('login*') && ! $request->is('oauth/authorize*')) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($this->securityService->isIpBlocked($ip)) {
            $block = $this->securityService->getActiveBlock($ip);
            $expiryText = $block?->expires_at
                ? $block->expires_at->diffForHumans()
                : 'permanen';

            $message = "Akses IP ({$ip}) diblokir sementara karena terdeteksi aktivitas mencurigakan. Silakan coba lagi nanti ({$expiryText}) Proteksi Bruteforce SiPintu.";

            if ($request->expectsJson() || $request->is('api/*') || $request->is('oauth/*')) {
                return response()->json([
                    'error' => 'ip_blocked',
                    'error_description' => $message,
                    'blocked_until' => $block?->expires_at?->toIso8601String(),
                ], 403);
            }

            return response()->view('errors.403', [
                'exception' => new \Exception($message),
                'message' => $message,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Determine if request is for static PWA assets or manifest files.
     */
    protected function isBypassableAsset(Request $request): bool
    {
        return $request->is([
            'manifest.webmanifest',
            'manifest.json',
            'sw.js',
            'icons/*',
            'apple-touch-icon.png',
            'offline.html',
            'favicon.ico',
        ]);
    }
}
