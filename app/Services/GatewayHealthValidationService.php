<?php

namespace App\Services;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class GatewayHealthValidationService
{
    /**
     * Get summary of downstream client applications connected to our REST API
     */
    public function getDownstreamClientsSummary(): array
    {
        $applications = Application::with('category')->get();

        $connected = 0;
        $disconnected = 0;
        $neverConnected = 0;

        $clientList = $applications->map(function (Application $app) use (&$connected, &$disconnected, &$neverConnected) {
            $status = $app->realtime_connection_status;

            if ($status === 'connected') {
                $connected++;
            } elseif ($status === 'disconnected') {
                $disconnected++;
            } else {
                $neverConnected++;
            }

            return [
                'id' => $app->id,
                'name' => $app->name,
                'client_id' => $app->client_id,
                'status' => $app->status,
                'connection_status' => $status,
                'last_connected_at' => $app->last_connected_at?->toIso8601String(),
                'last_connected_human' => $app->last_connected_at ? $app->last_connected_at->diffForHumans() : 'Belum pernah terkoneksi',
                'last_connected_ip' => $app->last_connected_ip ?: '-',
                'total_api_requests' => (int) $app->total_api_requests,
                'base_url' => $app->base_url,
                'health_check_url' => $app->health_check_url,
                'last_health_status' => $app->last_health_status,
            ];
        });

        return [
            'total_applications' => $applications->count(),
            'connected_count' => $connected,
            'disconnected_count' => $disconnected,
            'never_connected_count' => $neverConnected,
            'clients' => $clientList,
        ];
    }

    /**
     * Validate client credentials and record connection state for downstream application
     */
    public function validateClientConnection(string $clientId, ?string $clientSecret = null, bool $recordConnection = true): array
    {
        $app = Application::where('client_id', trim($clientId))->first();

        if (! $app) {
            return [
                'valid' => false,
                'is_connected' => false,
                'status' => 'client_not_found',
                'message' => "Aplikasi downstream dengan Client ID '{$clientId}' tidak ditemukan di sistem gateway.",
                'application' => null,
            ];
        }

        if ($app->status !== 'active') {
            return [
                'valid' => false,
                'is_connected' => false,
                'status' => 'client_inactive',
                'message' => "Aplikasi '{$app->name}' terdaftar tetapi berstatus '{$app->status}' (dinonaktifkan). Akses REST API ditolak.",
                'application' => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'client_id' => $app->client_id,
                    'status' => $app->status,
                ],
            ];
        }

        if ($clientSecret !== null) {
            $secretValid = ($clientSecret === $app->client_secret);
            if (! $secretValid && (str_starts_with($app->client_secret, '$2y$') || str_starts_with($app->client_secret, '$2a$'))) {
                try {
                    $secretValid = Hash::check($clientSecret, $app->client_secret);
                } catch (Throwable $e) {
                    $secretValid = false;
                }
            }

            if (! $secretValid) {
                return [
                    'valid' => false,
                    'is_connected' => false,
                    'status' => 'secret_mismatch',
                    'message' => "Client Secret untuk aplikasi '{$app->name}' tidak cocok. Verifikasi gagal.",
                    'application' => [
                        'id' => $app->id,
                        'name' => $app->name,
                        'client_id' => $app->client_id,
                        'status' => $app->status,
                    ],
                ];
            }
        }

        // Record connection event
        if ($recordConnection) {
            $app->recordApiConnection();
            $app->refresh();
        }

        return [
            'valid' => true,
            'is_connected' => true,
            'status' => 'verified_and_connected',
            'message' => "Aplikasi downstream '{$app->name}' berhasil terverifikasi dan terkoneksi ke REST API Gateway.",
            'application' => [
                'id' => $app->id,
                'name' => $app->name,
                'client_id' => $app->client_id,
                'status' => $app->status,
                'connection_status' => $app->realtime_connection_status,
                'last_connected_at' => $app->last_connected_at?->toIso8601String(),
                'last_connected_human' => $app->last_connected_at ? $app->last_connected_at->diffForHumans() : 'Baru saja',
                'last_connected_ip' => $app->last_connected_ip,
                'total_api_requests' => (int) $app->total_api_requests,
                'redirect_uri' => $app->redirect_uri,
            ],
        ];
    }

    /**
     * Check Database connectivity
     */
    public function validateDatabase(): array
    {
        $startTime = microtime(true);
        try {
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'online',
                'connected' => true,
                'driver' => DB::connection()->getDriverName(),
                'database_name' => DB::connection()->getDatabaseName(),
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'offline',
                'connected' => false,
                'latency_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Cache store connectivity
     */
    public function validateCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'gateway_ping_test_'.uniqid();
            Cache::put($testKey, 'ok', 10);
            $val = Cache::get($testKey);
            Cache::forget($testKey);
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $val === 'ok' ? 'operational' : 'degraded',
                'store' => config('cache.default', 'database'),
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'offline',
                'store' => config('cache.default', 'database'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run full gateway status diagnosis for downstream apps
     */
    public function validateFullGateway(): array
    {
        $db = $this->validateDatabase();
        $cache = $this->validateCache();
        $clientsSummary = $this->getDownstreamClientsSummary();

        return [
            'gateway_name' => 'SiPintu REST API Gateway',
            'timestamp' => now()->toIso8601String(),
            'database' => $db,
            'cache' => $cache,
            'downstream_clients' => $clientsSummary,
            'summary' => [
                'total_registered_clients' => $clientsSummary['total_applications'],
                'connected_clients' => $clientsSummary['connected_count'],
                'disconnected_clients' => $clientsSummary['disconnected_count'],
                'never_connected_clients' => $clientsSummary['never_connected_count'],
                'active_oauth_tokens' => OAuthAccessToken::where('revoked', false)->where('expires_at', '>', now())->count(),
            ],
        ];
    }
}
