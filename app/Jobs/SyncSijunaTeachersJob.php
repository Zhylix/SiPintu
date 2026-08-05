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

class SyncSijunaTeachersJob implements ShouldQueue
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
            $teachersData = $sijunaApi->getTeachers();
            $processedCount = 0;

            $teacherRole = Role::firstOrCreate(
                ['name' => 'teacher', 'guard_name' => 'web']
            );

            $userRows = [];
            $now = now()->toDateTimeString();
            $defaultPasswordHash = Hash::make('password');

            foreach ($teachersData as $teacher) {
                $nip = isset($teacher['nip']) ? (string) $teacher['nip'] : null;
                $externalId = (string) ($nip ?? $teacher['external_id'] ?? $teacher['id'] ?? '');
                $email = $teacher['email'] ?? $teacher['user']['email'] ?? ($externalId ? $externalId.'@guru.sekolah.id' : null);

                if (! $email && ! $externalId) {
                    continue;
                }

                $name = $teacher['nama'] ?? $teacher['name'] ?? 'Guru SIJUNA';
                $phone = $teacher['hp'] ?? $teacher['phone'] ?? null;
                $username = $nip ?? $teacher['username'] ?? ($teacher['user']['name'] ?? explode('@', $email)[0]);

                $userRows[] = [
                    'external_id' => $externalId ?: $email,
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'role' => 'teacher',
                    'phone' => $phone,
                    'status' => 'active',
                    'password' => $defaultPasswordHash,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($externalId) {
                    Cache::put("teacher:{$externalId}", [
                        'external_id' => $externalId,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'teacher',
                        'phone' => $phone,
                        'synced_at' => $now,
                    ], 86400);
                }

                $processedCount++;
            }

            // Bulk Upsert in chunks of 500 records
            foreach (array_chunk($userRows, 500) as $chunk) {
                User::upsert(
                    $chunk,
                    ['email'],
                    ['external_id', 'name', 'username', 'phone', 'status', 'role', 'updated_at']
                );
            }

            // Attach teacher role to all synced users in bulk using Spatie model_has_roles
            $teacherUserIds = User::where('role', 'teacher')->pluck('id')->toArray();
            $pivotData = array_map(fn ($userId) => [
                'role_id' => $teacherRole->id,
                'model_type' => User::class,
                'model_id' => $userId,
            ], $teacherUserIds);

            foreach (array_chunk($pivotData, 500) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }

            $syncLog->update([
                'status' => 'success',
                'records_processed' => $processedCount,
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_teacher_sync_completed', [
                'records_processed' => $processedCount,
                'sync_log_id' => $syncLog->id,
            ]);

            Log::info("SIJUNA Teacher Sync completed successfully. Processed: {$processedCount}");
        } catch (Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_teacher_sync_failed', [
                'error' => $e->getMessage(),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::error('SIJUNA Teacher Sync failed: '.$e->getMessage());
            throw $e;
        }
    }
}
