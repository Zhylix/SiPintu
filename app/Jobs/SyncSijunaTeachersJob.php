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

    public ?array $summary = null;

    public function __construct()
    {
        //
    }

    public function handle(SijunaApiService $sijunaApi): array
    {
        $syncLog = SyncLog::create([
            'sync_type' => 'sijuna_teachers',
            'status' => 'in_progress',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $teachersData = $sijunaApi->getTeachers();
            $processedCount = 0;
            $teachersCount = 0;
            $skipped = [];

            $teacherRole = Role::firstOrCreate(
                ['name' => 'teacher', 'guard_name' => 'web']
            );

            $userRows = [];
            $now = now()->toDateTimeString();
            $defaultPasswordHash = Hash::make('password');

            foreach ($teachersData as $index => $teacher) {
                $nip = isset($teacher['nip']) ? trim((string) $teacher['nip']) : null;
                $externalId = trim((string) ($nip ?? $teacher['external_id'] ?? $teacher['id'] ?? ''));
                $email = $teacher['email'] ?? $teacher['user']['email'] ?? ($externalId ? $externalId.'@guru.sekolah.id' : null);
                $name = $teacher['nama'] ?? $teacher['name'] ?? null;

                if (! $email && ! $externalId) {
                    $displayName = $name ?: ('Data Guru #'.($index + 1));
                    $skipped[] = [
                        'identifier' => $displayName,
                        'reason' => 'NIP, External ID, dan Email kosong / tidak ditemukan',
                    ];

                    continue;
                }

                if (! $name) {
                    $name = 'Guru SIJUNA ('.($nip ?: $externalId).')';
                }

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

                $teachersCount++;
                $processedCount++;
            }

            // Bulk Upsert in chunks of 500 records
            foreach (array_chunk($userRows, 500) as $chunk) {
                User::upsert(
                    $chunk,
                    ['external_id'],
                    ['name', 'email', 'username', 'phone', 'status', 'role', 'updated_at']
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

            $apiWarning = $sijunaApi->getLastTeacherError();
            $usedFallback = $sijunaApi->usedTeacherFallback();

            $noteParts = [];
            if ($usedFallback) {
                $noteParts[] = '[Fallback Digunakan] '.($apiWarning ?: 'Endpoint SIJUNA offline');
            } elseif ($apiWarning) {
                $noteParts[] = $apiWarning;
            }
            if (! empty($skipped)) {
                $reasonsSummary = implode(', ', array_map(fn ($s) => "{$s['identifier']} ({$s['reason']})", array_slice($skipped, 0, 3)));
                if (count($skipped) > 3) {
                    $reasonsSummary .= ', dan '.(count($skipped) - 3).' data lainnya';
                }
                $noteParts[] = count($skipped).' data dilewati: '.$reasonsSummary;
            }
            $noteMessage = ! empty($noteParts) ? implode(' | ', $noteParts) : null;

            $summary = [
                'sync_type' => 'sijuna_teachers',
                'status' => 'success',
                'records_processed' => $processedCount,
                'teachers_count' => $teachersCount,
                'skipped_count' => count($skipped),
                'skipped_items' => $skipped,
                'used_fallback' => $usedFallback,
                'warning' => $apiWarning,
                'note' => $noteMessage,
            ];

            $syncLog->update([
                'status' => 'success',
                'records_processed' => $processedCount,
                'error_message' => $noteMessage,
                'details' => $summary,
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_teacher_sync_completed', [
                'records_processed' => $processedCount,
                'teachers_count' => $teachersCount,
                'skipped_count' => count($skipped),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::info("SIJUNA Teacher Sync completed. Guru: {$teachersCount}, Skipped: ".count($skipped));

            $this->summary = $summary;

            return $summary;
        } catch (Exception $e) {
            $summary = [
                'sync_type' => 'sijuna_teachers',
                'status' => 'failed',
                'records_processed' => 0,
                'teachers_count' => 0,
                'skipped_count' => 0,
                'skipped_items' => [],
                'error' => $e->getMessage(),
            ];

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'details' => $summary,
                'completed_at' => now(),
            ]);

            AuditLogger::log('sijuna_teacher_sync_failed', [
                'error' => $e->getMessage(),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::error('SIJUNA Teacher Sync failed: '.$e->getMessage());
            $this->summary = $summary;
            throw $e;
        }
    }
}
