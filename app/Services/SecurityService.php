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

        // 1. Explicitly configured IP whitelist in config/env
        $configuredWhitelist = array_filter(array_map('trim', explode(',', (string) config('auth.security.ip_whitelist', env('SECURITY_IP_WHITELIST', '')))));
        if (in_array($ip, $configuredWhitelist, true)) {
            return true;
        }

        // 2. Private LAN / internal network IPs in non-testing environments (10.*, 192.168.*, 172.16-31.*, 127.0.0.1, ::1)
        if (! app()->environment('testing')) {
            $isPrivateOrReserved = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
            if ($isPrivateOrReserved) {
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
     * Record a failed login attempt for an IP and identifier.
     * Automatically triggers temporary IP block when threshold is reached:
     * - Either repeated failed attempts on the same target account (default 5x)
     * - Or cumulative failed attempts across all accounts from this IP (default 30x)
     * - Whitelisted / internal school networks are protected from automatic bans.
     */
    public function recordFailedLogin(?string $ip, ?string $identifier, ?int $userId = null): int
    {
        $ip = $ip ?: (request()->ip() ?: '127.0.0.1');
        $identifierKey = strtolower(trim((string) $identifier));

        $maxAttempts = (int) config('auth.security.max_attempts', self::MAX_ATTEMPTS);
        $maxIpAttempts = (int) config('auth.security.max_ip_attempts', self::MAX_IP_ATTEMPTS);
        $blockMinutes = (int) config('auth.security.block_minutes', self::BLOCK_MINUTES);

        // 1. Track total failed attempts for this IP
        $ipCacheKey = "security:failed_login_count:{$ip}";
        $isFreshIp = Cache::get($ipCacheKey) === null;
        $ipAttempts = (int) Cache::get($ipCacheKey, 0) + 1;
        Cache::put($ipCacheKey, $ipAttempts, now()->addMinutes($blockMinutes));

        // 2. Track failed attempts specifically for this target account on this IP
        $accountCacheKey = "security:failed_login_account:".md5($identifierKey.'|'.$ip);
        // If IP cache was just cleared (e.g. via Cache::forget in tests/maintenance), reset account attempts too
        $accountAttempts = ($isFreshIp ? 0 : (int) Cache::get($accountCacheKey, 0)) + 1;
        Cache::put($accountCacheKey, $accountAttempts, now()->addMinutes($blockMinutes));

        $isAccountThresholdReached = ! empty($identifierKey) && $accountAttempts >= $maxAttempts;
        $isIpThresholdReached = $ipAttempts >= $maxIpAttempts;
        $shouldBlock = ($isAccountThresholdReached || $isIpThresholdReached) && ! $this->isWhitelistedIp($ip);

        SecurityLog::create([
            'user_id' => $userId,
            'target_identifier' => $identifier,
            'event_type' => 'login_failed',
            'severity' => ($isAccountThresholdReached || $isIpThresholdReached) ? 'critical' : 'warning',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'payload' => [
                'attempt_number' => $accountAttempts,
                'ip_total_attempts' => $ipAttempts,
                'max_attempts' => $maxAttempts,
                'max_ip_attempts' => $maxIpAttempts,
            ],
        ]);

        if ($shouldBlock) {
            $expiresAt = now()->addMinutes($blockMinutes);
            $reason = $isAccountThresholdReached
                ? 'Diblokir otomatis oleh sistem: Terlalu banyak percobaan login gagal pada akun '.$identifier.' ('.$accountAttempts.'x).'
                : 'Diblokir otomatis oleh sistem: Terdeteksi aktivitas brute-force beruntun pada IP ini ('.$ipAttempts.'x gagal).';

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
                    'account_failed_attempts' => $accountAttempts,
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
            $identifier = $user->email ?? $user->username ?? (string) $user->id;
            $identifierKey = strtolower(trim((string) $identifier));

            // Clear account failure count
            Cache::forget("security:failed_login_account:".md5($identifierKey.'|'.$ip));

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
}
