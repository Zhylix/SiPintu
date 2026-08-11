<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SijunaApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SyncSijunaStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(SijunaApiService $sijunaApi): void
    {
        $syncLog = SyncLog::create([
            'sync_type' => 'sijuna_students',
            'status' => 'in_progress',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $studentsData = $sijunaApi->getStudents();
            $processedCount = 0;

            $studentRole = Role::firstOrCreate(
                ['name' => 'student', 'guard_name' => 'web']
            );
            $alumniRole = Role::firstOrCreate(
                ['name' => 'alumni', 'guard_name' => 'web']
            );

            $userRows = [];
            $now = now()->toDateTimeString();
            $defaultPasswordHash = Hash::make('password');

            foreach ($studentsData as $student) {
                $nis = isset($student['nis']) ? (string) $student['nis'] : null;
                $externalId = (string) ($nis ?? $student['external_id'] ?? $student['id'] ?? '');
                if (! $externalId) {
                    continue;
                }

                $name = $student['nama'] ?? $student['name'] ?? 'Siswa SIJUNA';
                $email = $student['user']['email'] ?? $student['email'] ?? ($externalId.'@siswa.sekolah.id');
                $phone = $student['hp'] ?? $student['phone'] ?? null;
                $username = $nis ?? ($student['user']['name'] ?? $externalId);

                // Classroom null/empty means Alumni, classroom filled means active Student
                $rawClassroom = $student['classroom'] ?? $student['kelas'] ?? $student['classroom_name'] ?? $student['class'] ?? null;
                if (is_array($rawClassroom)) {
                    $classroom = $rawClassroom['name'] ?? $rawClassroom['nama'] ?? $rawClassroom['title'] ?? null;
                } else {
                    $classroom = $rawClassroom;
                }

                $isAlumni = is_null($classroom) || trim((string) $classroom) === '' || strtolower(trim((string) $classroom)) === 'null';
                $assignedRole = $isAlumni ? 'alumni' : 'student';

                $userRows[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'role' => $assignedRole,
                    'phone' => $phone,
                    'status' => 'active',
                    'password' => $defaultPasswordHash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                Cache::put("user:{$externalId}", [
                    'external_id' => $externalId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $assignedRole,
                    'classroom' => $classroom,
                    'phone' => $phone,
                    'synced_at' => $now,
                ], 86400);

                $processedCount++;
            }

            // Bulk Upsert in chunks of 500 records
            foreach (array_chunk($userRows, 500) as $chunk) {
                User::upsert(
                    $chunk,
                    ['external_id'],
                    ['name', 'email', 'username', 'phone', 'status', 'role', 'password', 'updated_at']
                );
            }

            // Attach student & alumni roles in bulk using Spatie model_has_roles
            $syncedUsers = User::whereIn('role', ['student', 'alumni'])->get(['id', 'role']);
            $pivotData = [];
            foreach ($syncedUsers as $u) {
                $rId = ($u->role === 'alumni') ? $alumniRole->id : $studentRole->id;
                $pivotData[] = [
                    'role_id' => $rId,
                    'model_type' => User::class,
                    'model_id' => $u->id,
                ];
            }

            foreach (array_chunk($pivotData, 500) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }

            $syncLog->update([
                'status' => 'success',
                'records_processed' => $processedCount,
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_sync_completed', [
                'records_processed' => $processedCount,
                'sync_log_id' => $syncLog->id,
            ]);

            Log::info("SIJUNA Student Sync completed successfully. Processed: {$processedCount}");
        } catch (Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_sync_failed', [
                'error' => $e->getMessage(),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::error('SIJUNA Student Sync failed: '.$e->getMessage());
            throw $e;
        }
    }
}
