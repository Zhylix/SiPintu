<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public function broadcastPasswordChange(User $user): array
    {
        // Admin users are exempt from automatic downstream password sync (KEC ADMIN)
        if ($user->isAdmin()) {
            return [
                'status' => 'skipped',
                'message' => 'Administrator users are exempt from automatic downstream password synchronization.',
            ];
        }

        $activeApps = Application::where('status', 'active')->get();
        $results = [];

        foreach ($activeApps as $app) {
            $webhookUrl = rtrim($app->base_url, '/').'/api/sipintu/sync-password';

            try {
                $payload = array_merge([
                    'event' => 'user.password_updated',
                    'user_id' => (string) $user->id,
                    'external_id' => $user->external_id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                    'updated_at' => now()->toIso8601String(),
                ], $this->getPasswordPayload($user));

                $response = Http::timeout(3)
                    ->withHeaders([
                        'X-SiPintu-Event' => 'user.password_updated',
                        'X-SiPintu-Client-ID' => $app->client_id,
                    ])
                    ->post($webhookUrl, $payload);

                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'status' => $response->successful() ? 'synced' : 'failed',
                    'http_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        AuditLogger::log('sso_password_broadcast', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'apps_count' => count($activeApps),
            'results' => $results,
        ], $user->id);

        return [
            'status' => 'success',
            'synced_apps_count' => count($activeApps),
            'details' => $results,
        ];
    }
}
