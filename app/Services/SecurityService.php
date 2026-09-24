<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SecurityService
{
    public const MAX_ATTEMPTS = 5;

    public const BLOCK_MINUTES = 15;

    /**
     * Check if the given IP address is currently blocked.
     */
    public function isIpBlocked(?string $ip = null): bool
    {
        $ip = $ip ?: request()->ip();
        if (! $ip) {
            return false;
        }

        return BlockedIp::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Retrieve active block record for an IP address.
     */
    public function getActiveBlock(?string $ip = null): ?BlockedIp
    {
        $ip = $ip ?: request()->ip();
        if (! $ip) {
            return null;
        }

        return BlockedIp::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Record a failed login attempt for an IP and identifier.
     * Automatically triggers temporary IP block when threshold is reached.
     */
    public function recordFailedLogin(?string $ip, ?string $identifier, ?int $userId = null): int
    {
        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $cacheKey = "security:failed_login_count:{$ip}";

        $attempts = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, now()->addMinutes(self::BLOCK_MINUTES));

        SecurityLog::create([
            'user_id' => $userId,
            'target_identifier' => $identifier,
            'event_type' => 'login_failed',
            'severity' => $attempts >= self::MAX_ATTEMPTS ? 'critical' : 'warning',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'payload' => [
                'attempt_number' => $attempts,
                'max_attempts' => self::MAX_ATTEMPTS,
            ],
        ]);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $expiresAt = now()->addMinutes(self::BLOCK_MINUTES);

            BlockedIp::updateOrCreate(
                ['ip_address' => $ip],
                [
                    'reason' => 'Diblokir otomatis oleh sistem: Terlalu banyak percobaan login gagal ('.$attempts.'x).',
                    'expires_at' => $expiresAt,
                    'is_active' => true,
                ]
            );

            SecurityLog::create([
                'user_id' => $userId,
                'target_identifier' => $identifier,
                'event_type' => 'brute_force_detected',
                'severity' => 'critical',
                'ip_address' => $ip,
                'user_agent' => request()->userAgent(),
                'payload' => [
                    'action' => 'ip_auto_blocked',
                    'blocked_until' => $expiresAt->toDateTimeString(),
                    'total_failed' => $attempts,
                ],
            ]);
        }

        return $attempts;
    }

    /**
     * Clear failed attempts when a user successfully logs in.
     */
    public function recordSuccessfulLogin(?string $ip, User $user): void
    {
        $ip = $ip ?: request()->ip();
        if ($ip) {
            Cache::forget("security:failed_login_count:{$ip}");
        }

        SecurityLog::create([
            'user_id' => $user->id,
            'target_identifier' => $user->email ?? $user->username ?? (string) $user->id,
            'event_type' => 'login_success',
            'severity' => 'info',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'payload' => [
                'role' => $user->role,
            ],
        ]);
    }
}
