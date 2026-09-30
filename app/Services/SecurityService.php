<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SecurityService
{
    public const MAX_ATTEMPTS = 5;

    public const MAX_IP_ATTEMPTS = 30;

    public const BLOCK_MINUTES = 15;

    /**
     * Check if an IP address is whitelisted against automatic IP banning.
     */
    public function isWhitelistedIp(?string $ip = null): bool
    {
        $ip = $ip ?: request()->ip();
        if (! $ip) {
            return false;
        }

        // Local loopback is always safe
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        // Configured whitelist from .env (comma-separated, supports wildcards e.g. 192.168.*, 10.0.*)
        $configuredWhitelist = array_filter(array_map('trim', explode(',', (string) config('auth.security.ip_whitelist', env('SECURITY_IP_WHITELIST', '')))));
        foreach ($configuredWhitelist as $allowed) {
            if ($ip === $allowed || (str_contains($allowed, '*') && fnmatch($allowed, $ip))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given IP address is currently blocked.
     */
    public function isIpBlocked(?string $ip = null): bool
    {
        $ip = $ip ?: request()->ip();
        if (! $ip) {
            return false;
        }

        $activeBlock = BlockedIp::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $activeBlock) {
            return false;
        }

        // If block was set manually by an administrator, respect the block
        if ($activeBlock->blocked_by !== null) {
            return true;
        }

        // If auto-blocked by system, bypass if whitelisted
        if ($this->isWhitelistedIp($ip)) {
            return false;
        }

        return true;
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
     * Check if a specific user account is temporarily locked on an IP.
     */
    public function isAccountLocked(?string $identifier, ?string $ip = null): bool
    {
        if (empty($identifier)) {
            return false;
        }

        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $identifierKey = strtolower(trim((string) $identifier));
        $cacheKey = "security:account_locked:".md5($identifierKey.'|'.$ip);

        return Cache::has($cacheKey);
    }

    /**
     * Get remaining lockout minutes for a locked account.
     */
    public function getAccountLockRemainingMinutes(?string $identifier, ?string $ip = null): int
    {
        if (empty($identifier)) {
            return 0;
        }

        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $identifierKey = strtolower(trim((string) $identifier));
        $cacheKey = "security:account_locked:".md5($identifierKey.'|'.$ip);

        $lockedUntil = Cache::get($cacheKey);
        if (! $lockedUntil) {
            return 0;
        }

        return max(1, (int) now()->diffInMinutes($lockedUntil, false));
    }

    /**
     * Record a failed login attempt for an IP and identifier.
     * Accurately distinguishes between single-account mistakes and IP brute force:
     * - Reaching account limit locks ONLY that account on this IP (does not block other students).
     * - Reaching cumulative IP limit blocks the entire IP.
     */
    public function recordFailedLogin(?string $ip, ?string $identifier, ?int $userId = null): int
    {
        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $identifierKey = strtolower(trim((string) $identifier));

        $maxAttempts = (int) config('auth.security.max_attempts', self::MAX_ATTEMPTS);
        $maxIpAttempts = (int) config('auth.security.max_ip_attempts', self::MAX_IP_ATTEMPTS);
        $blockMinutes = (int) config('auth.security.block_minutes', self::BLOCK_MINUTES);

        // 1. Track cumulative failures for this IP
        $ipCacheKey = "security:failed_login_count:{$ip}";
        $isFreshIp = Cache::get($ipCacheKey) === null;
        $ipAttempts = (int) Cache::get($ipCacheKey, 0) + 1;
        Cache::put($ipCacheKey, $ipAttempts, now()->addMinutes($blockMinutes));

        // 2. Track failures specifically for this target account on this IP
        $accountCacheKey = "security:failed_login_account:".md5($identifierKey.'|'.$ip);
        $accountAttempts = ($isFreshIp ? 0 : (int) Cache::get($accountCacheKey, 0)) + 1;
        Cache::put($accountCacheKey, $accountAttempts, now()->addMinutes($blockMinutes));

        $isAccountLocked = false;
        if (! empty($identifierKey) && $accountAttempts >= $maxAttempts) {
            $lockExpiry = now()->addMinutes($blockMinutes);
            Cache::put("security:account_locked:".md5($identifierKey.'|'.$ip), $lockExpiry, $lockExpiry);
            $isAccountLocked = true;
        }

        $isIpThresholdReached = $ipAttempts >= $maxIpAttempts;
        $shouldBlockIp = $isIpThresholdReached && ! $this->isWhitelistedIp($ip);

        SecurityLog::create([
            'user_id' => $userId,
            'target_identifier' => $identifier,
            'event_type' => $isAccountLocked ? 'account_locked' : 'login_failed',
            'severity' => ($shouldBlockIp || $isAccountLocked) ? 'critical' : 'warning',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'payload' => [
                'account_attempts' => $accountAttempts,
                'ip_total_attempts' => $ipAttempts,
                'max_attempts' => $maxAttempts,
                'max_ip_attempts' => $maxIpAttempts,
                'account_locked' => $isAccountLocked,
            ],
        ]);

        if ($shouldBlockIp) {
            $expiresAt = now()->addMinutes($blockMinutes);
            $reason = "Diblokir otomatis oleh sistem: Terdeteksi aktivitas brute-force beruntun pada IP ini ({$ipAttempts}x gagal).";

            BlockedIp::updateOrCreate(
                ['ip_address' => $ip],
                [
                    'reason' => $reason,
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
                    'ip_failed_attempts' => $ipAttempts,
                    'reason' => $reason,
                ],
            ]);
        }

        return $accountAttempts;
    }

    /**
     * Clear failed attempts when a user successfully logs in.
     * Also decays cumulative IP failure count to prevent background noise from accumulating.
     */
    public function recordSuccessfulLogin(?string $ip, User $user): void
    {
        $ip = $ip ?: request()->ip();
        if ($ip) {
            $identifiersToClear = array_filter(array_unique([
                strtolower(trim((string) $user->email)),
                strtolower(trim((string) $user->username)),
                strtolower(trim((string) $user->external_id)),
                (string) $user->id,
            ]));

            // Clear account failure count and lock state for all identifiers of this user
            foreach ($identifiersToClear as $idKey) {
                Cache::forget("security:failed_login_account:".md5($idKey.'|'.$ip));
                Cache::forget("security:account_locked:".md5($idKey.'|'.$ip));
            }

            // Decrement IP failure count on legitimate login so school networks stay healthy
            $blockMinutes = (int) config('auth.security.block_minutes', self::BLOCK_MINUTES);
            $ipAttempts = (int) Cache::get("security:failed_login_count:{$ip}", 0);
            if ($ipAttempts > 0) {
                Cache::put("security:failed_login_count:{$ip}", max(0, $ipAttempts - 1), now()->addMinutes($blockMinutes));
            }
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

    /**
     * Clear all security caches for an IP.
     */
    public function clearAllIpSecurityCache(string $ip): void
    {
        Cache::forget("security:failed_login_count:{$ip}");
    }
}
