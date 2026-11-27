<?php

namespace App\Services;

use App\Jobs\SendDownstreamUserSyncWebhookJob;
use App\Models\User;

class PasswordSyncService
{
    /**
     * Generate standard password sync payload for a user.
     * Admin users are exempt (KEC ADMIN).
     */
    public function getPasswordPayload(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'password_sync_required' => false,
                'password_change_policy' => 'ADMIN_EXEMPT',
                'can_change_password_externally' => true,
            ];
        }

        return [
            'password' => $user->password,
            'password_hash' => $user->password,
            'password_sync_required' => true,
            'password_change_policy' => 'MUST_CHANGE_IN_SIPINTU_ONLY',
            'can_change_password_externally' => false,
        ];
    }

    /**
     * Broadcast updated user password to all active downstream SSO client applications.
     */
    public function broadcastPasswordChange(User $user, bool $sync = false): array
    {
        // Admin users are exempt from automatic downstream password sync (KEC ADMIN)
        if ($user->isAdmin()) {
            return [
                'status' => 'skipped',
                'message' => 'Administrator users are exempt from automatic downstream password synchronization.',
            ];
        }

        if ($sync || app()->runningInConsole()) {
            return app(UserDataSyncService::class)->broadcastUserUpdate($user, ['password']);
        }

        SendDownstreamUserSyncWebhookJob::dispatch($user, ['password'])->afterResponse();

        return [
            'status' => 'queued',
            'message' => 'Downstream password synchronization dispatched to background queue.',
        ];
    }
}
