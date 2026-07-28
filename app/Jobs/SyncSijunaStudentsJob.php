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

            foreach ($studentsData as $student) {
                $externalId = $student['external_id'] ?? $student['id'] ?? null;
                if (!$externalId) {
                    continue;
                }

                $email = $student['email'] ?? ($externalId . '@siswa.sekolah.id');
                $name = $student['name'] ?? 'Siswa SIJUNA';
                $phone = $student['phone'] ?? null;

                // Sync student into Gateway users table
                $user = User::updateOrCreate(
                    ['external_id' => $externalId],
                    [
                        'name' => $name,
                        'email' => $email,
                        'user_type' => 'student',
                        'phone' => $phone,
                        'status' => 'active',
                        // Student account does not store plain or weak password.
                        // Default hashed placeholder if null. Real auth relies on SIJUNA auth flow.
                        'password' => User::where('external_id', $externalId)->value('password') ?? Hash::make('SIJUNA_SSO_STUDENT_'.uniqid()),
                    ]
                );

                // Assign student role
                if (!$user->roles->contains($studentRole->id)) {
                    $user->roles()->attach($studentRole->id);
                }

                // Populate Redis Cache (user:{external_id})
                Cache::put("user:{$externalId}", [
                    'external_id' => $externalId,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'student',
                    'user_type' => 'student',
                    'phone' => $phone,
                    'synced_at' => now()->toDateTimeString(),
                ], 86400);

                $processedCount++;
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
