<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserDataSyncService
{
    /**
     * Cache key tracking to prevent duplicate broadcasts within the same request lifecycle.
     */
    protected static array $broadcastedInRequest = [];

    /**
     * Generate standard user sync payload for downstream applications.
     */
    public function getUserPayload(User $user, array $changes = [], array $previous = []): array
    {
        $isAdmin = $user->isAdmin();

        $userData = [
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'username' => $user->username,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'classroom' => $user->classroom,
            'phone' => $user->phone,
            'status' => $user->status,
            'avatar_url' => $user->avatar_url,
            'avatar' => $user->avatar_url,
            'jurusan_id' => $user->jurusan_id,
            'kode_jurusan' => $user->jurusan?->kode_jurusan,
            'nama_jurusan' => $user->jurusan?->nama_jurusan,
            'tahun_masuk' => $user->tahun_masuk,
            'tahun_lulus' => $user->isAlumni() ? $user->tahun_lulus : null,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];

        // Password hash synchronization policy (Admins are exempt from transmitting password hashes)
        if ($isAdmin) {
            $userData['password_sync_required'] = false;
            $userData['password_change_policy'] = 'ADMIN_EXEMPT';
            $userData['can_change_password_externally'] = true;
        } else {
            $userData['password'] = $user->password;
            $userData['password_hash'] = $user->password;
            $userData['password_sync_required'] = true;
            $userData['password_change_policy'] = 'MUST_CHANGE_IN_SIPINTU_ONLY';
            $userData['can_change_password_externally'] = false;
        }

        return [
            'event' => 'user.updated',
            'event_id' => (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'user' => $userData,
            'changed_fields' => array_values($changes),
            'previous' => $previous,
        ];
    }

    /**
     * Broadcast user data change to all active downstream SSO client applications.
     */
    public function broadcastUserUpdate(User $user, array $changes = [], array $previous = [], bool $force = false): array
    {
        $cacheKey = $user->id.'_'.implode(',', $changes).'_'.($user->updated_at?->timestamp ?? time());
        if (! $force && isset(static::$broadcastedInRequest[$cacheKey])) {
            return static::$broadcastedInRequest[$cacheKey];
        }

        $activeApps = Application::where('status', 'active')->get();
        $results = [];

        if ($activeApps->isEmpty()) {
            $response = [
                'status' => 'skipped',
                'message' => 'No active downstream applications registered.',
                'synced_apps_count' => 0,
                'details' => [],
            ];
            static::$broadcastedInRequest[$cacheKey] = $response;

            return $response;
        }

        $payload = $this->getUserPayload($user, $changes, $previous);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $currentSchemeAndHost = request()->getSchemeAndHttpHost();
        $currentHost = parse_url($currentSchemeAndHost, PHP_URL_HOST);
        $currentPort = parse_url($currentSchemeAndHost, PHP_URL_PORT) ?? (request()->isSecure() ? 443 : 80);
        $loopbackHosts = array_filter(array_unique([
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            $currentHost,
        ]));

        foreach ($activeApps as $app) {
            $baseUrl = trim((string) ($app->base_url ?? ''));
            if (empty($baseUrl) || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'target_url' => $baseUrl,
                    'status' => 'skipped',
                    'message' => 'Skipped downstream synchronization due to empty or invalid base_url.',
                ];
                continue;
            }

            $targetUrl = rtrim($baseUrl, '/').'/api/sipintu/sync-user';
            $fallbackUrl = rtrim($baseUrl, '/').'/api/sipintu/sync-password';
            $clientSecret = $app->client_secret ?? '';
            $signature = hash_hmac('sha256', $payloadJson, $clientSecret);

            // Prevent self-deadlock when downstream app base_url points to this exact local server instance
            $appHost = parse_url($baseUrl, PHP_URL_HOST);
            $appPort = parse_url($baseUrl, PHP_URL_PORT) ?? (parse_url($baseUrl, PHP_URL_SCHEME) === 'https' ? 443 : 80);

            $isSelfRequest = ($appHost && in_array($appHost, $loopbackHosts, true) && (int) $appPort === (int) $currentPort)
                || ($currentHost && $appHost && $appHost === $currentHost && (int) $appPort === (int) $currentPort);

            if ($isSelfRequest) {
                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'target_url' => $targetUrl,
                    'status' => 'skipped',
                    'message' => 'Skipped self-synchronization to prevent server deadlock.',
                ];
                continue;
            }

            try {
                $startTime = microtime(true);

                // 1. Attempt sending to modern /api/sipintu/sync-user webhook
                $response = Http::connectTimeout(2)->timeout(3)
                    ->withHeaders([
                        'X-SiPintu-Event' => 'user.updated',
                        'X-SiPintu-Client-ID' => $app->client_id,
                        'X-SiPintu-Signature' => $signature,
                        'X-SiPintu-Timestamp' => (string) now()->timestamp,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->withBody($payloadJson, 'application/json')
                    ->post($targetUrl);

                $latency = round((microtime(true) - $startTime) * 1000, 2);

                // 2. If 404 and changes contain password, try legacy fallback endpoint /api/sipintu/sync-password
                if ($response->status() === 404 && in_array('password', $changes) && ! $user->isAdmin()) {
                    $passwordPayload = [
                        'event' => 'user.password_updated',
                        'user_id' => (string) $user->id,
                        'external_id' => $user->external_id,
                        'email' => $user->email,
                        'username' => $user->username,
                        'role' => $user->role,
                        'password' => $user->password,
                        'password_hash' => $user->password,
                        'updated_at' => now()->toIso8601String(),
                    ];

                    $fallbackSignature = hash_hmac('sha256', (string) json_encode($passwordPayload), $clientSecret);

                    $response = Http::connectTimeout(2)->timeout(3)
                        ->withHeaders([
                            'X-SiPintu-Event' => 'user.password_updated',
                            'X-SiPintu-Client-ID' => $app->client_id,
                            'X-SiPintu-Signature' => $fallbackSignature,
                            'X-SiPintu-Timestamp' => (string) now()->timestamp,
                            'Accept' => 'application/json',
                        ])
                        ->post($fallbackUrl, $passwordPayload);
                }

                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'target_url' => $targetUrl,
                    'status' => $response->successful() ? 'synced' : 'failed',
                    'http_code' => $response->status(),
                    'latency_ms' => $latency,
                    'response_body' => Str::limit($response->body(), 200),
                ];
            } catch (\Throwable $e) {
                Log::warning("[UserDataSyncService] Failed syncing user {$user->id} to {$app->name} ({$app->client_id}): ".$e->getMessage());

                $results[$app->id] = [
                    'app_name' => $app->name,
                    'client_id' => $app->client_id,
                    'target_url' => $targetUrl,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        AuditLogger::log('user_data_sync_broadcast', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'changed_fields' => $changes,
            'apps_count' => $activeApps->count(),
            'results' => $results,
        ], $user->id);

        $output = [
            'status' => 'success',
            'synced_apps_count' => $activeApps->count(),
            'details' => $results,
        ];

        static::$broadcastedInRequest[$cacheKey] = $output;

        return $output;
    }
}
