<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CheckApplicationHealthJob;
use App\Models\Application;
use App\Models\OAuthAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminMonitoringController extends Controller
{
    public function index()
    {
        // 1. Database Connection Status
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Online';
        } catch (\Throwable $e) {
            $dbStatus = 'Error: ' . $e->getMessage();
        }

        // 2. Redis Cache Status
        try {
            $redisPing = Redis::ping();
            $redisStatus = $redisPing ? 'Connected' : 'Unavailable';
        } catch (\Throwable $e) {
            $redisStatus = 'Offline / Driver missing';
        }

        // 3. Applications Status Matrix
        $applications = Application::all();

        // 4. OAuth Active Tokens Statistics
        $activeTokens = OAuthAccessToken::where('revoked', false)
            ->where('expires_at', '>', now())
            ->count();

        return view('admin.monitoring.index', compact('dbStatus', 'redisStatus', 'applications', 'activeTokens'));
    }

    public function runHealthChecks()
    {
        CheckApplicationHealthJob::dispatchSync();
        return back()->with('success', 'Health check seluruh aplikasi berhasil diperbarui.');
    }
}
