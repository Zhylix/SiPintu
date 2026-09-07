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

    /**
     * Jalankan diagnosa otomatis koneksi SSO untuk aplikasi downstream spesifik
     */
    public function diagnoseSso(Request $request, \App\Services\SsoDiagnosticsService $diagnosticsService): JsonResponse
    {
        $clientId = $request->input('client_id');
        $appId = $request->input('application_id');
        $secret = $request->input('client_secret');

        $query = Application::query();
        if ($clientId) {
            $query->where('client_id', trim($clientId));
        } elseif ($appId) {
            $query->where('id', $appId);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Harap sertakan client_id atau application_id.',
            ], 400);
        }

        $app = $query->first();
        if (! $app) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aplikasi downstream tidak ditemukan di sistem.',
            ], 404);
        }

        $diagnosis = $diagnosticsService->diagnose($app, $secret ? (string) $secret : null);

        return response()->json([
            'status' => 'success',
            'data' => $diagnosis,
        ]);
    }

    /**
     * Jalankan diagnosa koneksi SSO massal untuk seluruh aplikasi downstream
     */
    public function diagnoseAllSso(\App\Services\SsoDiagnosticsService $diagnosticsService): JsonResponse
    {
        $results = $diagnosticsService->diagnoseAll();

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }
}

