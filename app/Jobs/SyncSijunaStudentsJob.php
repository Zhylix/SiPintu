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
            'status' => 'in_progress',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $studentsData = $sijunaApi->getStudents();
            $processedCount = 0;

            $studentRole = Role::firstOrCreate(
                ['slug' => 'student'],
                ['name' => 'Siswa', 'description' => 'Akun Siswa dari SIJUNA']
            );

            $userRows = [];
            $now = now()->toDateTimeString();
            $defaultPasswordHash = Hash::make('SIJUNA_SSO_STUDENT_PASSTHROUGH');

            foreach ($studentsData as $student) {
                $nis = isset($student['nis']) ? (string) $student['nis'] : null;
                $externalId = (string) ($nis ?? $student['external_id'] ?? $student['id'] ?? '');
                if (!$externalId) {
                    continue;
                }

                $name = $student['nama'] ?? $student['name'] ?? 'Siswa SIJUNA';
                $email = $student['user']['email'] ?? $student['email'] ?? ($externalId . '@siswa.sekolah.id');
                $phone = $student['hp'] ?? $student['phone'] ?? null;
                $username = $nis ?? ($student['user']['name'] ?? $externalId);

                $userRows[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'user_type' => 'student',
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
                    'role' => 'student',
                    'user_type' => 'student',
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
                    ['name', 'email', 'username', 'phone', 'status', 'user_type', 'updated_at']
                );
            }

            // Attach student role to all synced users in bulk
            $studentUserIds = User::where('user_type', 'student')->pluck('id')->toArray();
            $pivotData = array_map(fn($userId) => [
                'user_id' => $userId,
                'role_id' => $studentRole->id,
            ], $studentUserIds);

            foreach (array_chunk($pivotData, 500) as $chunk) {
                \Illuminate\Support\Facades\DB::table('role_user')->insertOrIgnore($chunk);
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

            Log::error("SIJUNA Student Sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
