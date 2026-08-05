<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CheckApplicationHealthJob;
use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Services\GatewayHealthValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminMonitoringController extends Controller
{
    public function index(GatewayHealthValidationService $validator)
    {
        // 1. Full Gateway Diagnostics
        $gatewayDiagnostics = $validator->validateFullGateway();

        // 2. Database Connection Status
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Online';
        } catch (\Throwable $e) {
            $dbStatus = 'Error: '.$e->getMessage();
        }

        // 3. Redis Cache Status
        try {
            $redisPing = Redis::ping();
            $redisStatus = $redisPing ? 'Connected' : 'Unavailable';
        } catch (\Throwable $e) {
            $redisStatus = 'Offline / Driver missing';
        }

        // 4. Applications Status Matrix
        $applications = Application::all();

        // 5. OAuth Active Tokens Statistics
        $activeTokens = OAuthAccessToken::where('revoked', false)
            ->where('expires_at', '>', now())
            ->count();

        return view('admin.monitoring.index', compact(
            'dbStatus',
            'redisStatus',
            'applications',
            'activeTokens',
            'gatewayDiagnostics'
        ));
    }

    public function runHealthChecks()
    {
        CheckApplicationHealthJob::dispatchSync();

        return back()->with('success', 'Health check seluruh aplikasi berhasil diperbarui.');
    }

    public function validateGateway(GatewayHealthValidationService $validator): JsonResponse
    {
        $diagnostics = $validator->validateFullGateway();

        return response()->json([
            'status' => 'success',
            'data' => $diagnostics,
        ]);
    }

    public function validateClientApp(Request $request, GatewayHealthValidationService $validator): JsonResponse
    {
        $clientId = $request->input('client_id');
        $secret = $request->input('client_secret');

        if (! $clientId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client ID wajib diisi.',
            ], 400);
        }

        // Dry-run validation from Admin UI (do not alter client connection timestamp/counter)
        $result = $validator->validateClientConnection((string) $clientId, $secret ? (string) $secret : null, false);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
