<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CheckApplicationHealthJob;
use App\Models\Application;
use App\Models\BlockedIp;
use App\Models\OAuthAccessToken;
use App\Models\SecurityLog;
use App\Services\AuditLogger;
use App\Services\DatabaseBackupService;
use App\Services\GatewayHealthValidationService;
use App\Services\SsoDiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class AdminMonitoringController extends Controller
{
    public function index(GatewayHealthValidationService $validator, DatabaseBackupService $backupService)
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

        // 6. Database Backups List
        $backups = $backupService->listBackups();

        // 7. Security Center: Blocked IPs and Security Logs
        $blockedIps = BlockedIp::latest()->take(20)->get();
        $securityLogs = SecurityLog::with('user')->latest()->take(15)->get();
        $activeBlockedCount = BlockedIp::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $bruteForceCount24h = SecurityLog::where('event_type', 'brute_force_detected')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return view('admin.monitoring.index', compact(
            'dbStatus',
            'redisStatus',
            'applications',
            'activeTokens',
            'gatewayDiagnostics',
            'backups',
            'blockedIps',
            'securityLogs',
            'activeBlockedCount',
            'bruteForceCount24h'
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
    public function diagnoseSso(Request $request, SsoDiagnosticsService $diagnosticsService): JsonResponse
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
    public function diagnoseAllSso(SsoDiagnosticsService $diagnosticsService): JsonResponse
    {
        $results = $diagnosticsService->diagnoseAll();

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }

    /**
     * Buat backup database SiPintu secara langsung dari halaman Admin
     */
    public function createBackup(DatabaseBackupService $backupService)
    {
        $result = $backupService->createBackup(7, auth()->id());

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Unduh file backup database (.sql.gz)
     */
    public function downloadBackup(string $filename, DatabaseBackupService $backupService)
    {
        $path = $backupService->getBackupPath($filename);

        if (! $path) {
            abort(404, 'File backup database tidak ditemukan.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    /**
     * Hapus file backup database tertentu
     */
    public function deleteBackup(string $filename, DatabaseBackupService $backupService)
    {
        $deleted = $backupService->deleteBackup($filename);

        if (! $deleted) {
            return back()->with('error', 'Gagal menghapus file backup database.');
        }

        return back()->with('success', "File backup {$filename} berhasil dihapus.");
    }

    /**
     * Restore database dari file backup (.sql.gz) yang sudah ada.
     */
    public function restoreBackup(Request $request, string $filename, DatabaseBackupService $backupService)
    {
        $request->validate([
            'admin_password' => ['required', 'string'],
        ], [
            'admin_password.required' => 'Masukkan kata sandi Administrator untuk mengonfirmasi pemulihan database.',
        ]);

        if (! Hash::check($request->admin_password, auth()->user()->password)) {
            return back()->with('error', 'Konfirmasi kata sandi Administrator tidak sesuai. Pemulihan database dibatalkan demi keamanan.');
        }

        $result = $backupService->restoreBackup($filename, auth()->id());

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Buka blokir IP address tertentu secara manual.
     */
    public function unblockIp(BlockedIp $blockedIp)
    {
        $ip = $blockedIp->ip_address;
        $blockedIp->update([
            'is_active' => false,
            'expires_at' => now(),
        ]);

        // Clear failed login counter in cache for this IP
        Cache::forget("security:failed_login_count:{$ip}");

        AuditLogger::log('security_ip_unblocked', [
            'ip_address' => $ip,
            'unblocked_by' => auth()->user()->name,
        ], auth()->id());

        return back()->with('success', "Blokir pada IP address {$ip} berhasil dibuka.");
    }

    /**
     * Tambah blokir IP address secara manual oleh Administrator.
     */
    public function storeBlockedIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['required', 'string', 'max:255'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:8760'], // 0 = permanen
        ], [
            'ip_address.required' => 'IP Address wajib diisi.',
            'ip_address.ip' => 'Format IP Address tidak valid.',
            'reason.required' => 'Alasan pemblokiran wajib diisi.',
        ]);

        $duration = (int) ($validated['duration_hours'] ?? 0);
        $expiresAt = $duration > 0 ? now()->addHours($duration) : null;

        BlockedIp::updateOrCreate(
            ['ip_address' => $validated['ip_address']],
            [
                'reason' => 'Diblokir manual oleh Admin ('.auth()->user()->name.'): '.$validated['reason'],
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );

        AuditLogger::log('security_ip_blocked_manual', [
            'ip_address' => $validated['ip_address'],
            'reason' => $validated['reason'],
            'expires_at' => $expiresAt ? $expiresAt->toDateTimeString() : 'Permanen',
        ], auth()->id());

        return back()->with('success', "IP Address {$validated['ip_address']} berhasil dimasukkan ke daftar blokir.");
    }
}
