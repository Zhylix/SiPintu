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

    public function triggerSync(Request $request, SijunaApiService $sijunaApi): RedirectResponse
    {
        try {
            // Run sync synchronously for instant admin feedback (Students + Teachers)
            $studentJob = new SyncSijunaStudentsJob();
            $studentsSummary = $studentJob->handle($sijunaApi);

            $teacherJob = new SyncSijunaTeachersJob();
            $teachersSummary = $teacherJob->handle($sijunaApi);

            AuditLogger::log('admin_manual_sijuna_sync_triggered', [
                'students' => $studentsSummary,
                'teachers' => $teachersSummary,
            ]);

            // Clear dashboard cache so stats reflect immediately
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');

            // Hitung ringkasan per tipe data
            $studentsCount = (int) ($studentsSummary['students_count'] ?? 0);
            $alumniCount = (int) ($studentsSummary['alumni_count'] ?? 0);
            $teachersCount = (int) ($teachersSummary['teachers_count'] ?? 0);
            $totalFetched = $studentsCount + $alumniCount + $teachersCount;

            $skippedItems = array_merge(
                $studentsSummary['skipped_items'] ?? [],
                $teachersSummary['skipped_items'] ?? []
            );
            $skippedCount = count($skippedItems);

            $warnings = array_values(array_filter([
                $studentsSummary['warning'] ?? null,
                $teachersSummary['warning'] ?? null,
            ]));

            $syncReport = [
                'total_fetched' => $totalFetched,
                'types' => [
                    'siswa' => $studentsCount,
                    'alumni' => $alumniCount,
                    'guru' => $teachersCount,
                ],
                'skipped_count' => $skippedCount,
                'skipped_items' => $skippedItems,
                'warnings' => $warnings,
            ];

            // Susun pesan notifikasi berbasis tipe data saja
            $typeDetails = [];
            if ($studentsCount > 0) {
                $typeDetails[] = "{$studentsCount} Siswa Aktif";
            }
            if ($alumniCount > 0) {
                $typeDetails[] = "{$alumniCount} Alumni";
            }
            if ($teachersCount > 0) {
                $typeDetails[] = "{$teachersCount} Guru";
            }
            $typeString = ! empty($typeDetails) ? implode(', ', $typeDetails) : '0 data';

            $successMsg = "Sinkronisasi berhasil mengambil total {$totalFetched} data ({$typeString}).";
            if ($skippedCount > 0) {
                $successMsg .= " Perhatian: {$skippedCount} data belum diambil karena alasan validasi.";
            }

            return back()
                ->with('success', $successMsg)
                ->with('sync_report', $syncReport);
        } catch (Exception $e) {
            return back()
                ->with('error', 'Gagal menjalankan sinkronisasi: '.$e->getMessage())
                ->with('sync_error_detail', $e->getMessage());
        }
    }
}
