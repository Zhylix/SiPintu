<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SecurityService
{
    public const MAX_ATTEMPTS = 15;

    public const WINDOW_MINUTES = 3;

    public const TIMEOUT_MINUTES = 15;

    public const MAX_IP_ATTEMPTS = 15;

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
     * Check if the given IP address is currently blocked (manual admin block) or under temporary timeout.
     */
    public function isIpBlocked(?string $ip = null): bool
    {
        $ip = $ip ?: request()->ip();
        if (! $ip) {
            return false;
        }

        // 1. Manual Block by Administrator in Database
        $activeBlock = BlockedIp::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($activeBlock) {
            if ($activeBlock->blocked_by !== null) {
                return true;
            }

            if ($this->isWhitelistedIp($ip)) {
                return false;
            }

            return true;
        }

        // 2. Temporary 15-minute timeout for 15 failures in 3 minutes (in cache, no DB blocking)
        if (Cache::has("security:ip_timeout:{$ip}")) {
            return ! $this->isWhitelistedIp($ip);
        }

        return false;
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

        $activeBlock = BlockedIp::where('ip_address', $ip)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($activeBlock) {
            return $activeBlock;
        }

        $timeoutExpiry = Cache::get("security:ip_timeout:{$ip}");
        if ($timeoutExpiry) {
            return new BlockedIp([
                'ip_address' => $ip,
                'reason' => 'Time out 15 menit karena terdeteksi 15 kali percobaan login gagal dalam 3 menit.',
                'expires_at' => $timeoutExpiry,
                'is_active' => true,
            ]);
        }

        return null;
    }

    /**
     * Get remaining timeout minutes for an IP under temporary timeout.
     */
    public function getIpTimeoutRemainingMinutes(?string $ip = null): int
    {
        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $timeoutExpiry = Cache::get("security:ip_timeout:{$ip}");
        if (! $timeoutExpiry) {
            return 0;
        }

        return max(1, (int) now()->diffInMinutes($timeoutExpiry, false));
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
     * Enforces temporary 15-minute timeout for 15 failed attempts within 3 minutes (No permanent IP blocking).
     */
    public function recordFailedLogin(?string $ip, ?string $identifier, ?int $userId = null): int
    {
        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $identifierKey = strtolower(trim((string) $identifier));

        $maxAttempts = (int) config('auth.security.max_attempts', self::MAX_ATTEMPTS);
        $windowMinutes = (int) config('auth.security.window_minutes', self::WINDOW_MINUTES);
        $timeoutMinutes = (int) config('auth.security.timeout_minutes', self::TIMEOUT_MINUTES);
        $maxIpAttempts = (int) config('auth.security.max_ip_attempts', self::MAX_IP_ATTEMPTS);

        // 1. Track cumulative failures for this IP within 3 minutes
        $ipCacheKey = "security:failed_login_count:{$ip}";
        $ipAttempts = (int) Cache::get($ipCacheKey, 0) + 1;
        Cache::put($ipCacheKey, $ipAttempts, now()->addMinutes($windowMinutes));

        // 2. Track failures specifically for this target account on this IP within 3 minutes
        $accountCacheKey = "security:failed_login_account:".md5($identifierKey.'|'.$ip);
        $accountAttempts = (int) Cache::get($accountCacheKey, 0) + 1;
        Cache::put($accountCacheKey, $accountAttempts, now()->addMinutes($windowMinutes));

        $isAccountLocked = false;
        if (! empty($identifierKey) && $accountAttempts >= $maxAttempts) {
            $lockExpiry = now()->addMinutes($timeoutMinutes);
            Cache::put("security:account_locked:".md5($identifierKey.'|'.$ip), $lockExpiry, $lockExpiry);
            Cache::forget($accountCacheKey);
            $isAccountLocked = true;
        }

        $isIpThresholdReached = $ipAttempts >= $maxIpAttempts;
        $shouldTimeoutIp = $isIpThresholdReached && ! $this->isWhitelistedIp($ip);

        if ($shouldTimeoutIp) {
            $ipTimeoutExpiry = now()->addMinutes($timeoutMinutes);
            Cache::put("security:ip_timeout:{$ip}", $ipTimeoutExpiry, $ipTimeoutExpiry);
            Cache::forget($ipCacheKey);
        }

        SecurityLog::create([
            'user_id' => $userId,
            'target_identifier' => $identifier,
            'event_type' => $isAccountLocked ? 'account_timeout' : 'login_failed',
            'severity' => ($shouldTimeoutIp || $isAccountLocked) ? 'warning' : 'info',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'payload' => [
                'account_attempts' => $accountAttempts,
                'ip_total_attempts' => $ipAttempts,
                'max_attempts' => $maxAttempts,
                'window_minutes' => $windowMinutes,
                'timeout_minutes' => $timeoutMinutes,
                'account_timed_out' => $isAccountLocked,
                'ip_timed_out' => $shouldTimeoutIp,
            ],
        ]);

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
            $windowMinutes = (int) config('auth.security.window_minutes', self::WINDOW_MINUTES);
            $ipAttempts = (int) Cache::get("security:failed_login_count:{$ip}", 0);
            if ($ipAttempts > 0) {
                Cache::put("security:failed_login_count:{$ip}", max(0, $ipAttempts - 1), now()->addMinutes($windowMinutes));
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
        Cache::forget("security:ip_timeout:{$ip}");
    }
}
