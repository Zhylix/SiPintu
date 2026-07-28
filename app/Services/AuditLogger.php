<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $activity, array $metadata = [], ?int $userId = null): AuditLog
    {
        $resolvedUserId = $userId ?? Auth::id();
        
        return AuditLog::create([
            'user_id' => $resolvedUserId,
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
