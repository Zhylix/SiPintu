<?php

namespace App\Jobs;

use App\Models\Jurusan;
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

    public ?array $summary = null;

    public function __construct()
    {
        //
    }

    public function handle(SijunaApiService $sijunaApi): array
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
            $studentsCount = 0;
            $alumniCount = 0;
            $skipped = [];

            $studentRole = Role::firstOrCreate(
                ['name' => 'student', 'guard_name' => 'web']
            );
            $alumniRole = Role::firstOrCreate(
                ['name' => 'alumni', 'guard_name' => 'web']
            );

            $jurusanMap = Jurusan::pluck('id', 'kode_jurusan')->toArray();

            $userRows = [];
            $now = now()->toDateTimeString();
            $defaultPasswordHash = Hash::make('password');

            foreach ($studentsData as $index => $student) {
                $nis = isset($student['nis']) ? trim((string) $student['nis']) : null;
                $externalId = trim((string) ($nis ?? $student['external_id'] ?? $student['id'] ?? ''));
                $name = $student['nama'] ?? $student['name'] ?? null;

                if (! $externalId) {
                    $displayName = $name ?: ('Data Siswa #' . ($index + 1));
                    $skipped[] = [
                        'identifier' => $displayName,
                        'reason' => 'NIS atau External ID kosong / tidak ditemukan',
                    ];
                    continue;
                }

                if (! $name) {
                    $name = 'Siswa SIJUNA (' . $externalId . ')';
                }

                $email = $student['user']['email'] ?? $student['email'] ?? ($externalId.'@siswa.sekolah.id');
                $phone = $student['hp'] ?? $student['phone'] ?? null;
                $username = $nis ?? ($student['user']['name'] ?? $externalId);

                // Classroom
                $rawClassroom = $student['classroom'] ?? $student['kelas'] ?? $student['classroom_name'] ?? $student['class'] ?? null;
                if (is_array($rawClassroom)) {
                    $classroom = $rawClassroom['name'] ?? $rawClassroom['nama'] ?? $rawClassroom['title'] ?? null;
                } else {
                    $classroom = $rawClassroom;
                }

                // Ekstraksi Jurusan dengan regex SIJUNA (PPL, TO, AKL, PM, MPLB)
                $rawJurusan = $student['jurusan'] ?? $student['program_keahlian'] ?? $student['major'] ?? null;
                $kodeJurusan = Jurusan::extractKodeJurusan($classroom, $rawJurusan);

                // Fallback pencarian kode jurusan dari nama jika kelas kosong (misal: "Rian (Alumni RPL)")
                if (! $kodeJurusan && $name) {
                    if (preg_match('/\((?:Alumni\s+)?([A-Za-z\s]+?)\)/i', $name, $nameMatches)) {
                        $kodeJurusan = Jurusan::extractKodeJurusan($nameMatches[1]);
                    }
                }

                $jurusanId = ($kodeJurusan && isset($jurusanMap[$kodeJurusan])) ? $jurusanMap[$kodeJurusan] : null;

                // Filter Alumni: jika graduated = true maka alumni. Jika field graduated tidak tersedia, fallback ke pengecekan classroom.
                $graduatedRaw = $student['graduated'] ?? $student['is_graduated'] ?? null;
                if (! is_null($graduatedRaw)) {
                    $isAlumni = filter_var($graduatedRaw, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $isAlumni = is_null($classroom) || trim((string) $classroom) === '' || strtolower(trim((string) $classroom)) === 'null';
                }

                $assignedRole = $isAlumni ? 'alumni' : 'student';
                if ($isAlumni) {
                    $alumniCount++;
                } else {
                    $studentsCount++;
                }

                $userRows[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'role' => $assignedRole,
                    'classroom' => $classroom,
                    'jurusan_id' => $jurusanId,
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
                    'jurusan_id' => $jurusanId,
                    'kode_jurusan' => $kodeJurusan,
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
                    ['name', 'email', 'username', 'classroom', 'jurusan_id', 'phone', 'status', 'role', 'updated_at']
                );
            }

            // Attach student & alumni roles in bulk using Spatie model_has_roles
            $studentUserIds = User::where('role', 'student')->pluck('id')->toArray();
            $alumniUserIds = User::where('role', 'alumni')->pluck('id')->toArray();

            // Ensure alumni do not retain old student role
            if (! empty($alumniUserIds)) {
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $alumniUserIds)
                    ->where('role_id', $studentRole->id)
                    ->delete();
            }

            // Ensure active students do not retain alumni role
            if (! empty($studentUserIds)) {
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $studentUserIds)
                    ->where('role_id', $alumniRole->id)
                    ->delete();
            }

            $pivotData = [];
            foreach ($studentUserIds as $uId) {
                $pivotData[] = [
                    'role_id' => $studentRole->id,
                    'model_type' => User::class,
                    'model_id' => $uId,
                ];
            }
            foreach ($alumniUserIds as $uId) {
                $pivotData[] = [
                    'role_id' => $alumniRole->id,
                    'model_type' => User::class,
                    'model_id' => $uId,
                ];
            }

            foreach (array_chunk($pivotData, 500) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }

            $apiWarning = $sijunaApi->getLastStudentError();
            $usedFallback = $sijunaApi->usedStudentFallback();

            $noteParts = [];
            if ($usedFallback) {
                $noteParts[] = '[Fallback Digunakan] ' . ($apiWarning ?: 'Endpoint SIJUNA offline');
            } elseif ($apiWarning) {
                $noteParts[] = $apiWarning;
            }
            if (! empty($skipped)) {
                $reasonsSummary = implode(', ', array_map(fn ($s) => "{$s['identifier']} ({$s['reason']})", array_slice($skipped, 0, 3)));
                if (count($skipped) > 3) {
                    $reasonsSummary .= ', dan ' . (count($skipped) - 3) . ' data lainnya';
                }
                $noteParts[] = count($skipped) . ' data dilewati: ' . $reasonsSummary;
            }
            $noteMessage = ! empty($noteParts) ? implode(' | ', $noteParts) : null;

            $summary = [
                'sync_type' => 'sijuna_students',
                'status' => 'success',
                'records_processed' => $processedCount,
                'students_count' => $studentsCount,
                'alumni_count' => $alumniCount,
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

            AuditLogger::log('sijuna_sync_completed', [
                'records_processed' => $processedCount,
                'students_count' => $studentsCount,
                'alumni_count' => $alumniCount,
                'skipped_count' => count($skipped),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::info("SIJUNA Student Sync completed. Siswa: {$studentsCount}, Alumni: {$alumniCount}, Skipped: ".count($skipped));

            $this->summary = $summary;
            return $summary;
        } catch (Exception $e) {
            $summary = [
                'sync_type' => 'sijuna_students',
                'status' => 'failed',
                'records_processed' => 0,
                'students_count' => 0,
                'alumni_count' => 0,
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

            AuditLogger::log('sijuna_sync_failed', [
                'error' => $e->getMessage(),
                'sync_log_id' => $syncLog->id,
            ]);

            Log::error('SIJUNA Student Sync failed: '.$e->getMessage());
            $this->summary = $summary;
            throw $e;
        }
    }
}
