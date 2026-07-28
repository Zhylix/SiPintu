<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PklStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PklStatusController extends Controller
{
    /**
     * Update PKL Status.
     * RESTRICTED: Only DUDI and Admin users can change PKL status.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        // 1. Strict Role Authorization
        if (!$user || (!$user->isAdmin() && !$user->isDudi())) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak! Hanya Mitra DUDI dan Administrator yang diizinkan mengubah Status PKL.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys(PklStatus::allowedStatuses())),
            'company_name' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $pklStatus = PklStatus::findOrFail($id);
        $oldStatus = $pklStatus->status;

        $pklStatus->status = $request->status;
        if ($request->filled('company_name')) {
            $pklStatus->company_name = $request->company_name;
        }
        if ($request->filled('division')) {
            $pklStatus->division = $request->division;
        }
        if ($request->has('notes')) {
            $pklStatus->notes = $request->notes;
        }
        $pklStatus->updated_by = $user->id;
        $pklStatus->save();

        // Audit Log entry
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'UPDATE_PKL_STATUS',
                'description' => "Mengubah status PKL siswa #{$pklStatus->student_id} dari '{$oldStatus}' ke '{$pklStatus->status}'",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Ignore audit log error if table differs
        }

        return response()->json([
            'success' => true,
            'message' => "Status PKL berhasil diperbarui menjadi '{$pklStatus->status}'.",
            'data' => [
                'id' => $pklStatus->id,
                'student_id' => $pklStatus->student_id,
                'student_name' => $pklStatus->student?->name,
                'status' => $pklStatus->status,
                'badge_color' => $pklStatus->badge_color,
                'notes' => $pklStatus->notes,
                'updated_by' => $user->name,
                'updated_at' => $pklStatus->updated_at->diffForHumans(),
                'timestamp' => $pklStatus->updated_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get live PKL status data for current user or all students (Admin/DUDI).
     */
    public function getLiveStatus(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->isStudent()) {
            $status = PklStatus::where('student_id', $user->id)->first();
            if (!$status) {
                $status = PklStatus::create([
                    'student_id' => $user->id,
                    'status' => 'Aktif Berjalan',
                    'company_name' => 'PT Telekomunikasi Indonesia / Technopark',
                    'division' => 'Software & Network Engineering',
                    'notes' => 'Status awal sistem.',
                ]);
            }

            return response()->json([
                'success' => true,
                'is_single' => true,
                'status' => [
                    'id' => $status->id,
                    'status' => $status->status,
                    'badge_color' => $status->badge_color,
                    'company_name' => $status->company_name,
                    'division' => $status->division,
                    'notes' => $status->notes,
                    'mentor_name' => $status->mentor_name,
                    'dudi_supervisor' => $status->dudi_supervisor,
                    'updated_by' => $status->updater?->name ?? 'DUDI / Admin',
                    'updated_at' => $status->updated_at->diffForHumans(),
                    'timestamp' => $status->updated_at->toIso8601String(),
                ]
            ]);
        }

        // DUDI, Admin, Teacher: Return all student statuses
        $statuses = PklStatus::with('student', 'updater')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'student_name' => $s->student?->name ?? 'Siswa',
                'student_nisn' => $s->student?->external_id ?? '-',
                'company_name' => $s->company_name,
                'division' => $s->division,
                'status' => $s->status,
                'badge_color' => $s->badge_color,
                'notes' => $s->notes,
                'updated_by' => $s->updater?->name ?? 'System',
                'updated_at' => $s->updated_at->diffForHumans(),
                'timestamp' => $s->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'is_single' => false,
            'data' => $statuses,
        ]);
    }

    /**
     * Server-Sent Events (SSE) endpoint for real-time status streaming.
     */
    public function stream(Request $request)
    {
        return response()->stream(function () use ($request) {
            $lastChecksum = '';
            
            // Send SSE stream loop for real-time responsiveness
            for ($i = 0; $i < 10; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $user = Auth::user();
                if ($user && $user->isStudent()) {
                    $status = PklStatus::where('student_id', $user->id)->first();
                    $data = $status ? [
                        'id' => $status->id,
                        'status' => $status->status,
                        'badge_color' => $status->badge_color,
                        'company_name' => $status->company_name,
                        'division' => $status->division,
                        'notes' => $status->notes,
                        'updated_by' => $status->updater?->name ?? 'DUDI / Admin',
                        'updated_at' => $status->updated_at->toIso8601String(),
                    ] : null;
                } else {
                    $data = PklStatus::with('student')->get()->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'student_id' => $s->student_id,
                            'student_name' => $s->student?->name,
                            'status' => $s->status,
                            'badge_color' => $s->badge_color,
                            'updated_at' => $s->updated_at->toIso8601String(),
                        ];
                    });
                }

                $currentChecksum = md5(json_encode($data));
                if ($currentChecksum !== $lastChecksum) {
                    $lastChecksum = $currentChecksum;
                    echo "event: pkl-status-update\n";
                    echo "data: " . json_encode($data) . "\n\n";
                    ob_flush();
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
