<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $activity, array $metadata = [], int|string|null $userId = null): AuditLog
    {
        $resolvedUserId = $userId !== null ? (int) $userId : Auth::id();

        if ($resolvedUserId && ! User::where('id', $resolvedUserId)->exists()) {
            $resolvedUserId = null;
        }

        return AuditLog::create([
            'user_id' => $resolvedUserId,
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
