<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSijunaStudentsJob;
use App\Jobs\SyncSijunaTeachersJob;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SijunaApiService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSijunaController extends Controller
{
    public function index(SijunaApiService $sijunaService)
    {
        $token = config('services.sijuna.token', '');
        $tokenStatus = ! empty($token) ? '••••••••' : 'Belum Dikonfigurasi';

        $config = [
            'url' => config('services.sijuna.url'),
            'token_masked' => $tokenStatus,
            'timeout' => config('services.sijuna.timeout'),
            'retry_times' => config('services.sijuna.retry_times'),
        ];

        $syncLogs = SyncLog::latest()->paginate(15);
        $syncedStudentsCount = User::where('role', 'student')->count();
        $syncedAlumniCount = User::where('role', 'alumni')->count();
        $syncedTeachersCount = User::where('role', 'teacher')->count();
        $latestSync = SyncLog::latest()->first();

        return view('admin.sijuna.index', compact('config', 'syncLogs', 'syncedStudentsCount', 'syncedAlumniCount', 'syncedTeachersCount', 'latestSync'));
    }

    public function triggerSync(Request $request): RedirectResponse
    {
        try {
            // Run sync synchronously for instant admin feedback (Students + Teachers)
            SyncSijunaStudentsJob::dispatchSync();
            SyncSijunaTeachersJob::dispatchSync();

            AuditLogger::log('admin_manual_sijuna_sync_triggered');

            return back()->with('success', 'Sinkronisasi data siswa dan guru SIJUNA berhasil dijalankan.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menjalankan sinkronisasi: '.$e->getMessage());
        }
    }
}
