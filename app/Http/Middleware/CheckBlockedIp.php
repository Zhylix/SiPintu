<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
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
        $ip = $request->ip();

        if ($this->securityService->isIpBlocked($ip)) {
            $block = $this->securityService->getActiveBlock($ip);
            $expiryText = $block?->expires_at
                ? $block->expires_at->diffForHumans()
                : 'permanen';

            $message = "Akses IP ({$ip}) diblokir sementara karena terdeteksi aktivitas mencurigakan atau terlalu banyak percobaan login yang gagal. Silakan coba lagi nanti ({$expiryText}).";

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
}
