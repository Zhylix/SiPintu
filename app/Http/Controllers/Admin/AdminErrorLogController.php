<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Services\ErrorLoggerService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminErrorLogController extends Controller
{
    /**
     * Display a listing of system error logs.
     */
    public function index(Request $request): View
    {
        $stats = [
            'total' => ErrorLog::count(),
            'unresolved' => ErrorLog::unresolved()->count(),
            'resolved' => ErrorLog::resolved()->count(),
            'today' => ErrorLog::where('created_at', '>=', now()->startOfDay())->count(),
            'affected_users' => ErrorLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        $query = ErrorLog::with(['user', 'resolver']);

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by Status Code
        if ($request->filled('status_code') && $request->status_code !== 'all') {
            $query->where('status_code', (int) $request->status_code);
        }

        // Filter by Role
        if ($request->filled('role') && $request->role !== 'all') {
            if ($request->role === 'guest') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_role', $request->role);
            }
        }

        // Search Query
        if ($request->filled('search')) {
            $query->scopeSearch($query, trim($request->search));
        }

        $errorLogs = $query->latest('last_seen_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.error-logs.index', compact('errorLogs', 'stats'));
    }

    /**
     * Display detailed information about a specific error log.
     */
    public function show(ErrorLog $errorLog): View
    {
        $errorLog->load(['user', 'resolver']);

        return view('admin.error-logs.show', compact('errorLog'));
    }

    /**
     * Mark an error log as resolved.
     */
    public function resolve(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $errorLog->markAsResolved(Auth::id(), $request->resolution_notes);

        return back()->with('success', "Insiden error [{$errorLog->incident_code}] berhasil ditandai sebagai Selesai.");
    }

    /**
     * Mark an error log as ignored.
     */
    public function ignore(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->markAsIgnored(Auth::id());

        return back()->with('success', "Insiden error [{$errorLog->incident_code}] ditandai sebagai Diabaikan.");
    }

    /**
     * Mark an error log back as unresolved.
     */
    public function unresolve(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->markAsUnresolved();

        return back()->with('success', "Insiden error [{$errorLog->incident_code}] dikembalikan ke status Belum Selesai.");
    }

    /**
     * Batch resolve multiple error logs.
     */
    public function batchResolve(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) && $request->has('resolve_all_unresolved')) {
            $count = ErrorLog::unresolved()->count();
            ErrorLog::unresolved()->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'resolution_notes' => 'Diselesaikan secara massal oleh Administrator.',
            ]);

            return back()->with('success', "Berhasil menyelesaikan {$count} insiden error sekaligus.");
        }

        if (is_array($ids) && count($ids) > 0) {
            ErrorLog::whereIn('id', $ids)->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'resolution_notes' => 'Diselesaikan secara massal oleh Administrator.',
            ]);

            return back()->with('success', count($ids).' insiden error berhasil diselesaikan.');
        }

        return back()->with('warning', 'Tidak ada data error yang dipilih.');
    }

    /**
     * Delete an error log.
     */
    public function destroy(ErrorLog $errorLog): RedirectResponse
    {
        $code = $errorLog->incident_code;
        $errorLog->delete();

        return redirect()->route('admin.error-logs.index')
            ->with('success', "Log error [{$code}] berhasil dihapus dari sistem.");
    }

    /**
     * Delete all resolved/ignored logs to free up space.
     */
    public function clearResolved(): RedirectResponse
    {
        $deletedCount = ErrorLog::whereIn('status', ['resolved', 'ignored'])->delete();

        return back()->with('success', "Berhasil membersihkan {$deletedCount} log error yang telah selesai/diabaikan.");
    }

    /**
     * Trigger a safe test 500 error to verify error recording & notification system.
     */
    public function triggerTestError(Request $request): RedirectResponse
    {
        try {
            // Intentionally create a controlled test exception
            throw new Exception('Simulasi Uji Coba Error Server 500 SiPintu (Test Trigger oleh Admin)');
        } catch (Exception $e) {
            $recordedLog = ErrorLoggerService::record($e, $request);

            if ($recordedLog) {
                return redirect()->route('admin.error-logs.show', $recordedLog->id)
                    ->with('success', "Uji coba berhasil! Error server 500 telah otomatis tercatat [{$recordedLog->incident_code}] dan notifikasi admin telah dikirimkan.");
            }
        }

        return back()->with('error', 'Gagal memicu simulasi error.');
    }
}
