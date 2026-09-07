<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if this audit log record is a failed SSO attempt/access.
     */
    public function isSsoFailure(): bool
    {
        if (! empty($this->metadata['is_sso_failure']) || ! empty($this->metadata['sso_failed'])) {
            return true;
        }

        $ssoFailureActivities = [
            'sso_login_failed',
            'sso_login_failed_password',
            'sso_login_failed_suspended',
            'sso_login_failed_role_mismatch',
            'sso_access_denied',
            'sso_authorize_invalid_client',
            'sso_authorize_invalid_redirect',
            'token_exchange_invalid_secret',
            'token_exchange_invalid_code',
        ];

        if (in_array($this->activity, $ssoFailureActivities, true)) {
            return true;
        }

        if (str_starts_with($this->activity, 'sso_') && (str_contains($this->activity, 'fail') || str_contains($this->activity, 'denied') || str_contains($this->activity, 'invalid'))) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this audit log record is associated with SSO authentication flow.
     */
    public function isSsoEvent(): bool
    {
        return str_starts_with($this->activity, 'sso_')
            || str_starts_with($this->activity, 'token_exchange_')
            || ! empty($this->metadata['via_sso']);
    }
}

