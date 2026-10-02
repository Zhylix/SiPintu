<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Jurusan;
use App\Models\OAuthAccessToken;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GatewayHealthValidationService;
use App\Services\PasswordSyncService;
use App\Services\SecurityService;
use App\Services\SijunaApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ApiIdentityController extends Controller
{
    /**
     * Return primary identity object for the authenticated OAuth user
     */
    public function user(Request $request, PasswordSyncService $passwordSyncService): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');

        if (! $user) {
            return response()->json([
                'error' => 'user_not_found',
                'message' => 'Endpoint /api/v1/user membutuhkan OAuth Bearer token milik pengguna.',
            ], 404);
        }

        $primaryRole = $user->roles->first()?->name ?? $user->role;

        $response = array_merge([
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'username' => $user->username,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'avatar' => $user->avatar_url,
            'role' => $primaryRole,
            'user_type' => $user->role,
            'status' => $user->status,
            'classroom' => $user->classroom,
            'jurusan_id' => $user->jurusan_id,
            'kode_jurusan' => $user->jurusan?->kode_jurusan,
            'nama_jurusan' => $user->jurusan?->nama_jurusan,
            'jurusan' => $user->jurusan ? [
                'id' => $user->jurusan->id,
                'kode_jurusan' => $user->jurusan->kode_jurusan,
                'nama_jurusan' => $user->jurusan->nama_jurusan,
            ] : null,
            'tahun_masuk' => $user->tahun_masuk,
            'tahun_lulus' => $user->isAlumni() ? $user->tahun_lulus : null,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ], $passwordSyncService->getPasswordPayload($user));

        return response()->json($response);
    }

    /**
     * Return detailed profile data including role details, timestamps, and enriched SIJUNA data
     */
    public function profile(Request $request, SijunaApiService $sijunaService, PasswordSyncService $passwordSyncService): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');
        $app = $request->attributes->get('oauth_application');

        if (! $user) {
            return response()->json([
                'error' => 'user_not_found',
                'message' => 'Endpoint /api/v1/user/profile membutuhkan OAuth Bearer token milik pengguna.',
            ], 404);
        }

        $sijunaData = null;
        if ($user->external_id || $user->email) {
            if ($user->isTeacher()) {
                $sijunaData = $sijunaService->getTeacherByExternalId($user->external_id ?: $user->email);
            } elseif ($user->isAlumni()) {
                $sijunaData = $sijunaService->getAlumniByExternalId($user->external_id ?: $user->username ?: $user->email);
            } elseif ($user->isStudent()) {
                $sijunaData = $sijunaService->getStudentByExternalId($user->external_id ?: $user->username ?: $user->email);
            }
        }

        return response()->json(array_merge([
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'username' => $user->username,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'avatar' => $user->avatar_url,
            'role' => $user->role,
            'user_type' => $user->role,
            'status' => $user->status,
            'classroom' => $user->classroom,
            'jurusan_id' => $user->jurusan_id,
            'kode_jurusan' => $user->jurusan?->kode_jurusan,
            'nama_jurusan' => $user->jurusan?->nama_jurusan,
            'jurusan' => $user->jurusan ? [
                'id' => $user->jurusan->id,
                'kode_jurusan' => $user->jurusan->kode_jurusan,
                'nama_jurusan' => $user->jurusan->nama_jurusan,
                'deskripsi' => $user->jurusan->deskripsi,
            ] : null,
            'tahun_masuk' => $user->tahun_masuk,
            'tahun_lulus' => $user->isAlumni() ? $user->tahun_lulus : null,
            'roles' => $user->roles->pluck('name'),
            'sijuna_data' => $sijunaData,
            'accessed_via_app' => $app ? [
                'name' => $app->name,
                'client_id' => $app->client_id,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ], $passwordSyncService->getPasswordPayload($user)));
    }

    /**
     * Return list of roles and permissions for user
     */
    public function roles(Request $request): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');

        if (! $user) {
            return response()->json([
                'error' => 'user_not_found',
                'message' => 'Endpoint /api/v1/user/roles membutuhkan OAuth Bearer token milik pengguna.',
            ], 404);
        }

        return response()->json([
            'user_id' => (string) $user->id,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug ?? $role->name,
                ];
            }),
            'permissions' => $user->permissions()->pluck('name')->values(),
        ]);
    }

    /**
     * Endpoint for downstream applications to retrieve or verify updated user password hashes
     */
    public function passwordSync(Request $request, PasswordSyncService $passwordSyncService): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');
        $app = $request->attributes->get('oauth_application');

        $identifier = $request->input('email') ?: $request->input('external_id') ?: $request->input('user_id');
        if ($identifier) {
            // Only authenticated client applications with secret can look up arbitrary other users
            if (! $app) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya aplikasi downstream terdaftar yang dapat memverifikasi sinkronisasi akun pengguna lain.',
                ], 403);
            }

            $targetUser = User::where('email', $identifier)
                ->orWhere('external_id', $identifier)
                ->orWhere('id', $identifier)
                ->orWhere('username', $identifier)
                ->first();

            if ($targetUser) {
                $user = $targetUser;
            }
        }

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        return response()->json(array_merge([
            'status' => 'success',
            'user_id' => (string) $user->id,
            'email' => $user->email,
            'external_id' => $user->external_id,
            'role' => $user->role,
            'updated_at' => $user->updated_at?->toIso8601String(),
        ], $passwordSyncService->getPasswordPayload($user)));
    }

    /**
     * Gateway Proxy API: Retrieve students data from SIJUNA (with Redis caching and local DB fallback)
     */
    public function students(Request $request, SijunaApiService $sijunaService): JsonResponse
    {
        $nis = $request->query('nis');

        if ($nis) {
            $student = $sijunaService->getStudentByExternalId($nis);

            if (! $student) {
                $localUser = User::where('username', $nis)
                    ->orWhere('external_id', $nis)
                    ->first();

                if ($localUser) {
                    $student = [
                        'id' => (string) $localUser->id,
                        'external_id' => $localUser->external_id,
                        'nis' => $localUser->username ?: $localUser->external_id,
                        'nama' => $localUser->name,
                        'name' => $localUser->name,
                        'email' => $localUser->email,
                        'phone' => $localUser->phone,
                        'avatar_url' => $localUser->avatar_url,
                        'avatar' => $localUser->avatar_url,
                        'role' => $localUser->role,
                        'status' => $localUser->status,
                    ];
                }
            }

            if (! $student) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Data siswa dengan NIS {$nis} tidak ditemukan.",
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'source' => 'Gateway Proxy (SIJUNA Service + Cache + DB Fallback)',
                'data' => $student,
            ]);
        }

        $students = $sijunaService->getStudents();

        // Prioritize active students with valid classrooms first, then sort by id descending
        usort($students, function ($a, $b) {
            $classA = ! empty($a['classroom'] ?? $a['kelas'] ?? $a['classroom_name'] ?? $a['class'] ?? null);
            $classB = ! empty($b['classroom'] ?? $b['kelas'] ?? $b['classroom_name'] ?? $b['class'] ?? null);

            if ($classA !== $classB) {
                return $classA ? -1 : 1;
            }

            return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
        });

        $enrichedStudents = array_map(function ($s) {
            $createdYear = ! empty($s['created_at']) ? (int) Carbon::parse($s['created_at'])->format('Y') : null;
            $updatedYear = ! empty($s['updated_at']) ? (int) Carbon::parse($s['updated_at'])->format('Y') : null;
            $s['tahun_masuk'] = $createdYear;
            $s['tahun_lulus'] = $updatedYear;

            return $s;
        }, $students);

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache)',
            'count' => count($enrichedStudents),
            'data' => $enrichedStudents,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve specific student data from SIJUNA by External ID or NIS
     */
    public function studentDetail(Request $request, string $externalId, SijunaApiService $sijunaService): JsonResponse
    {
        $student = $sijunaService->getStudentByExternalId($externalId);

        if (! $student) {
            $localUser = User::where('username', $externalId)
                ->orWhere('external_id', $externalId)
                ->first();

            if ($localUser) {
                $student = [
                    'id' => (string) $localUser->id,
                    'external_id' => $localUser->external_id,
                    'nis' => $localUser->username ?: $localUser->external_id,
                    'nama' => $localUser->name,
                    'name' => $localUser->name,
                    'email' => $localUser->email,
                    'phone' => $localUser->phone,
                    'avatar_url' => $localUser->avatar_url,
                    'avatar' => $localUser->avatar_url,
                    'role' => $localUser->role,
                    'status' => $localUser->status,
                    'classroom' => $localUser->classroom,
                    'created_at' => $localUser->created_at?->toIso8601String(),
                    'updated_at' => $localUser->updated_at?->toIso8601String(),
                ];
            }
        }

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => "Data siswa dengan ID/NIS {$externalId} tidak ditemukan.",
            ], 404);
        }

        $student['tahun_masuk'] = ! empty($student['created_at']) ? (int) Carbon::parse($student['created_at'])->format('Y') : null;
        $student['tahun_lulus'] = ! empty($student['updated_at']) ? (int) Carbon::parse($student['updated_at'])->format('Y') : null;

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache + DB Fallback)',
            'data' => $student,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve alumni data from SIJUNA (https://sijuna.com/api/external/alumni)
     */
    public function alumniProxy(Request $request, SijunaApiService $sijunaService): JsonResponse
    {
        $nis = $request->query('nis');

        if ($nis) {
            $alumni = $sijunaService->getAlumniByExternalId($nis);

            if (! $alumni) {
                $localUser = User::where('role', 'alumni')
                    ->where(function ($q) use ($nis) {
                        $q->where('username', $nis)
                            ->orWhere('external_id', $nis);
                    })
                    ->first();

                if ($localUser) {
                    $alumni = [
                        'id' => (string) $localUser->id,
                        'external_id' => $localUser->external_id,
                        'nis' => $localUser->username ?: $localUser->external_id,
                        'nama' => $localUser->name,
                        'name' => $localUser->name,
                        'email' => $localUser->email,
                        'phone' => $localUser->phone,
                        'avatar_url' => $localUser->avatar_url,
                        'avatar' => $localUser->avatar_url,
                        'role' => $localUser->role,
                        'status' => $localUser->status,
                        'classroom' => $localUser->classroom,
                        'tahun_masuk' => $localUser->tahun_masuk,
                        'tahun_lulus' => $localUser->tahun_lulus,
                    ];
                }
            }

            if (! $alumni) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Data alumni dengan NIS {$nis} tidak ditemukan.",
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'source' => 'Gateway Proxy (SIJUNA Alumni Service + Cache + DB Fallback)',
                'data' => $alumni,
            ]);
        }

        $alumniList = $sijunaService->getAlumni();

        $enrichedAlumni = array_map(function ($a) {
            $createdYear = ! empty($a['created_at']) ? (int) Carbon::parse($a['created_at'])->format('Y') : null;
            $updatedYear = ! empty($a['updated_at']) ? (int) Carbon::parse($a['updated_at'])->format('Y') : null;
            $a['tahun_masuk'] = $createdYear;
            $a['tahun_lulus'] = $updatedYear;

            return $a;
        }, $alumniList);

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Alumni Service + Redis Cache)',
            'count' => count($enrichedAlumni),
            'data' => $enrichedAlumni,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve specific alumni data from SIJUNA by External ID or NIS
     */
    public function alumniDetailProxy(Request $request, string $externalId, SijunaApiService $sijunaService): JsonResponse
    {
        $alumni = $sijunaService->getAlumniByExternalId($externalId);

        if (! $alumni) {
            $localUser = User::where('role', 'alumni')
                ->where(function ($q) use ($externalId) {
                    $q->where('username', $externalId)
                        ->orWhere('external_id', $externalId);
                })
                ->first();

            if ($localUser) {
                $alumni = [
                    'id' => (string) $localUser->id,
                    'external_id' => $localUser->external_id,
                    'nis' => $localUser->username ?: $localUser->external_id,
                    'nama' => $localUser->name,
                    'name' => $localUser->name,
                    'email' => $localUser->email,
                    'phone' => $localUser->phone,
                    'avatar_url' => $localUser->avatar_url,
                    'avatar' => $localUser->avatar_url,
                    'role' => $localUser->role,
                    'status' => $localUser->status,
                    'classroom' => $localUser->classroom,
                    'tahun_masuk' => $localUser->tahun_masuk,
                    'tahun_lulus' => $localUser->tahun_lulus,
                    'created_at' => $localUser->created_at?->toIso8601String(),
                    'updated_at' => $localUser->updated_at?->toIso8601String(),
                ];
            }
        }

        if (! $alumni) {
            return response()->json([
                'status' => 'error',
                'message' => "Data alumni dengan External ID / NIS {$externalId} tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Alumni Service + Redis Cache + DB Fallback)',
            'data' => $alumni,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve teachers data from SIJUNA (https://sijuna.com/api/guru)
     */
    public function teachers(Request $request, SijunaApiService $sijunaService): JsonResponse
    {
        $nip = $request->query('nip') ?: $request->query('email');

        if ($nip) {
            $teacher = $sijunaService->getTeacherByExternalId($nip);

            if (! $teacher) {
                $localUser = User::where('email', $nip)
                    ->orWhere('username', $nip)
                    ->orWhere('external_id', $nip)
                    ->first();

                if ($localUser && $localUser->isTeacher()) {
                    $teacher = [
                        'id' => (string) $localUser->id,
                        'external_id' => $localUser->external_id,
                        'nip' => $localUser->username ?: $localUser->external_id,
                        'nama' => $localUser->name,
                        'name' => $localUser->name,
                        'email' => $localUser->email,
                        'role' => $localUser->role,
                        'status' => $localUser->status,
                    ];
                }
            }

            if (! $teacher) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Data guru dengan Identifier/NIP/Email {$nip} tidak ditemukan.",
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'source' => 'Gateway Proxy (SIJUNA Service + Cache + DB Fallback)',
                'data' => $teacher,
            ]);
        }

        $teachers = $sijunaService->getTeachers();

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache)',
            'count' => count($teachers),
            'data' => $teachers,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve specific teacher data from SIJUNA by External ID / NIP / Email
     */
    public function teacherDetail(Request $request, string $externalId, SijunaApiService $sijunaService): JsonResponse
    {
        $teacher = $sijunaService->getTeacherByExternalId($externalId);

        if (! $teacher) {
            $localUser = User::where('email', $externalId)
                ->orWhere('username', $externalId)
                ->orWhere('external_id', $externalId)
                ->first();

            if ($localUser && $localUser->isTeacher()) {
                $teacher = [
                    'id' => (string) $localUser->id,
                    'external_id' => $localUser->external_id,
                    'nip' => $localUser->username ?: $localUser->external_id,
                    'nama' => $localUser->name,
                    'name' => $localUser->name,
                    'email' => $localUser->email,
                    'phone' => $localUser->phone,
                    'avatar_url' => $localUser->avatar_url,
                    'avatar' => $localUser->avatar_url,
                    'role' => $localUser->role,
                    'status' => $localUser->status,
                ];
            }
        }

        if (! $teacher) {
            return response()->json([
                'status' => 'error',
                'message' => "Data guru dengan ID/NIP/Email {$externalId} tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache + DB Fallback)',
            'data' => $teacher,
        ]);
    }

    /**
     * Public REST API Ping / Heartbeat check endpoint for downstream applications
     */
    public function ping(Request $request, GatewayHealthValidationService $service): JsonResponse
    {
        $clientId = $request->input('client_id') ?: $request->header('X-Client-ID');
        $clientApp = null;

        if ($clientId) {
            $validation = $service->validateClientConnection((string) $clientId, null, true);
            $clientApp = $validation['application'] ?? null;
        }

        $db = $service->validateDatabase();

        return response()->json([
            'status' => 'online',
            'gateway' => 'SiPintu REST API Gateway',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
            'database' => [
                'status' => $db['status'],
                'latency_ms' => $db['latency_ms'],
            ],
            'client_connection' => $clientApp ? [
                'registered' => true,
                'client_id' => $clientApp['client_id'],
                'name' => $clientApp['name'],
                'status' => 'connected',
                'last_connected_at' => $clientApp['last_connected_at'],
                'total_api_requests' => $clientApp['total_api_requests'],
            ] : [
                'registered' => false,
                'message' => 'Kirim parameter client_id atau header X-Client-ID untuk merekam heartbeat koneksi aplikasi downstream Anda.',
            ],
            'message' => 'REST API Gateway aktif dan siap melayani request dari aplikasi downstream.',
        ]);
    }

    /**
     * Validate downstream application credentials and verify active connection state
     */
    public function validateClientCredentials(Request $request, GatewayHealthValidationService $service): JsonResponse
    {
        $clientId = $request->input('client_id') ?: $request->header('X-Client-ID');
        $clientSecret = $request->input('client_secret') ?: $request->header('X-Client-Secret');

        if (! $clientId) {
            return response()->json([
                'valid' => false,
                'is_connected' => false,
                'status' => 'missing_parameters',
                'message' => 'Parameter client_id wajib diberikan via request body or header X-Client-ID.',
            ], 400);
        }

        $result = $service->validateClientConnection((string) $clientId, $clientSecret ? (string) $clientSecret : null, true);

        $httpCode = $result['valid'] ? 200 : ($result['status'] === 'client_not_found' ? 404 : 401);

        return response()->json($result, $httpCode);
    }

    /**
     * Verify credentials directly for downstream login form fallback and real-time password sync
     */
    public function verifyCredentials(Request $request, PasswordSyncService $passwordSyncService): JsonResponse
    {
        $clientId = $request->input('client_id') ?: $request->header('X-Client-ID');
        $clientSecret = $request->input('client_secret') ?: $request->header('X-Client-Secret');
        $identity = trim((string) ($request->input('identity') ?: $request->input('username') ?: $request->input('email')));
        $password = (string) $request->input('password');

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'valid' => false,
                'status' => 'missing_client_credentials',
                'message' => 'Parameter client_id dan client_secret wajib diisi.',
            ], 401);
        }

        $application = Application::where('client_id', $clientId)->first();
        if (! $application || $application->status !== 'active') {
            return response()->json([
                'valid' => false,
                'status' => 'invalid_client',
                'message' => 'Aplikasi klien tidak ditemukan atau tidak aktif.',
            ], 401);
        }

        $secretValid = hash_equals((string) $application->client_secret, (string) $clientSecret)
            || (is_string($application->client_secret) && str_starts_with($application->client_secret, '$2y$') && Hash::check($clientSecret, $application->client_secret));

        if (! $secretValid) {
            AuditLogger::log('api_verify_credentials_invalid_secret', [
                'client_id' => $clientId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'valid' => false,
                'status' => 'invalid_client_secret',
                'message' => 'Client secret tidak cocok.',
            ], 401);
        }

        if (empty($identity) || empty($password)) {
            return response()->json([
                'valid' => false,
                'status' => 'missing_parameters',
                'message' => 'Parameter identity (NIS/Email/Username) dan password wajib diisi.',
            ], 400);
        }

        $securityService = app(SecurityService::class);
        $clientIp = $request->ip() ?: '127.0.0.1';

        // Check if account is temporarily locked due to consecutive bruteforce attempts
        if ($securityService->isAccountLocked($identity, $clientIp)) {
            $remaining = $securityService->getAccountLockRemainingMinutes($identity, $clientIp);

            return response()->json([
                'valid' => false,
                'status' => 'account_locked',
                'message' => "Akun ini sementara terkunci karena terlalu banyak percobaan gagal. Silakan coba lagi dalam {$remaining} menit.",
            ], 429);
        }

        $user = User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('external_id', $identity)
            ->first();

        // Support prefix email lookup for NIS (e.g. 12345@smkn1bangsri.sch.id)
        if (! $user && ! str_contains($identity, '@')) {
            $user = User::where('email', 'like', $identity.'@%')->first();
        }

        if (! $user) {
            $securityService->recordFailedLogin($clientIp, $identity, null);

            return response()->json([
                'valid' => false,
                'status' => 'user_not_found',
                'message' => 'Akun pengguna tidak ditemukan di SiPintu Gateway.',
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'valid' => false,
                'status' => 'user_inactive',
                'message' => 'Akun pengguna sedang dinonaktifkan atau ditangguhkan.',
            ], 403);
        }

        if (! Hash::check($password, $user->password)) {
            $securityService->recordFailedLogin($clientIp, $identity, $user->id);

            AuditLogger::log('api_verify_credentials_failed', [
                'client_id' => $clientId,
                'identity' => $identity,
                'ip' => $clientIp,
            ]);

            return response()->json([
                'valid' => false,
                'status' => 'invalid_credentials',
                'message' => 'Kata sandi tidak cocok dengan data master SiPintu Gateway.',
            ], 401);
        }

        // Reset failed attempt cache on success
        Cache::forget('security:failed_login_account:'.md5(strtolower(trim($identity)).'|'.$clientIp));

        AuditLogger::log('api_verify_credentials_success', [
            'client_id' => $clientId,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $primaryRole = $user->roles->first()?->name ?? $user->role;
        $isAdmin = $user->isAdmin() || $user->hasRole('admin');

        return response()->json([
            'valid' => true,
            'status' => 'success',
            'message' => 'Kredensial pengguna valid.',
            'user' => [
                'id' => (string) $user->id,
                'external_id' => $user->external_id,
                'username' => $user->username,
                'nis' => $user->nis,
                'nip' => $user->nip,
                'dudi_code' => $user->dudi_code,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $primaryRole,
                'user_type' => $user->role,
                'classroom' => $user->classroom,
                'phone' => $user->phone,
                'status' => $user->status,
                'avatar_url' => $user->avatar_url,
                'avatar' => $user->avatar_url,
                'jurusan_id' => $user->jurusan_id,
                'kode_jurusan' => $user->jurusan?->kode_jurusan,
                'nama_jurusan' => $user->jurusan?->nama_jurusan,
                'jurusan' => $user->jurusan ? [
                    'id' => $user->jurusan->id,
                    'kode_jurusan' => $user->jurusan->kode_jurusan,
                    'nama_jurusan' => $user->jurusan->nama_jurusan,
                ] : null,
                'tahun_masuk' => $user->tahun_masuk,
                'tahun_lulus' => $user->isAlumni() ? $user->tahun_lulus : null,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
            'password' => $isAdmin ? null : $user->password,
            'password_hash' => $isAdmin ? null : $user->password,
            'password_sync_required' => ! $isAdmin,
            'password_change_policy' => $isAdmin ? 'ADMIN_EXEMPT' : 'MUST_CHANGE_IN_SIPINTU_ONLY',
            'can_change_password_externally' => $isAdmin,
        ]);
    }

    /**
     * Return connection status and summary of downstream applications connected to our REST API
     */
    public function gatewayStatus(Request $request, GatewayHealthValidationService $service): JsonResponse
    {
        $diagnosticData = $service->validateFullGateway();

        $app = $request->attributes->get('oauth_application');
        $user = $request->attributes->get('oauth_user');

        if ($app) {
            $diagnosticData['requesting_client'] = [
                'app_name' => $app->name,
                'client_id' => $app->client_id,
                'connection_status' => $app->realtime_connection_status,
                'last_connected_human' => $app->last_connected_at?->diffForHumans(),
                'total_api_requests' => $app->total_api_requests,
            ];
        }

        if ($user) {
            $diagnosticData['requesting_user'] = [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ];
        }

        return response()->json($diagnosticData);
    }

    /**
     * Endpoint API untuk aplikasi downstream mengambil daftar 5 Jurusan Resmi SMKN 1 Bangsri
     */
    public function jurusans(): JsonResponse
    {
        $jurusans = Jurusan::withCount(['alumni', 'students'])
            ->orderByRaw("FIELD(kode_jurusan, 'PPLG', 'TO', 'AKL', 'PM', 'MPLB')")
            ->get()
            ->map(function ($j) {
                return [
                    'id' => $j->id,
                    'kode_jurusan' => $j->kode_jurusan,
                    'nama_jurusan' => $j->nama_jurusan,
                    'deskripsi' => $j->deskripsi,
                    'total_alumni' => $j->alumni_count,
                    'total_siswa' => $j->students_count,
                ];
            });

        return response()->json([
            'status' => 'success',
            'count' => count($jurusans),
            'data' => $jurusans,
        ]);
    }

    /**
     * Endpoint API untuk aplikasi downstream mengambil detail jurusan berdasarkan kode
     */
    public function jurusanDetail(string $kode): JsonResponse
    {
        $normalizedKode = Jurusan::extractKodeJurusan($kode) ?: strtoupper(trim($kode));
        $jurusan = Jurusan::where('kode_jurusan', $normalizedKode)
            ->withCount(['alumni', 'students'])
            ->first();

        if (! $jurusan) {
            return response()->json([
                'status' => 'error',
                'message' => "Jurusan dengan kode '{$kode}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $jurusan->id,
                'kode_jurusan' => $jurusan->kode_jurusan,
                'nama_jurusan' => $jurusan->nama_jurusan,
                'deskripsi' => $jurusan->deskripsi,
                'total_alumni' => $jurusan->alumni_count,
                'total_siswa' => $jurusan->students_count,
            ],
        ]);
    }

    /**
     * Endpoint API untuk aplikasi downstream mengakses data alumni yang telah dikelompokkan sesuai jurusan
     */
    public function alumni(Request $request, ?string $kode = null): JsonResponse
    {
        // Catat koneksi jika downstream mengirimkan kredensial (Bearer token atau X-Client-ID)
        $tokenString = $request->bearerToken();
        $clientId = $request->header('X-Client-ID') ?: $request->input('client_id');
        $clientSecret = $request->header('X-Client-Secret') ?: $request->input('client_secret');
        $isAuthenticatedApp = false;

        if ($tokenString) {
            $token = OAuthAccessToken::where('token', $tokenString)->where('revoked', false)->where('expires_at', '>', now())->first();
            if ($token) {
                $isAuthenticatedApp = true;
                $token->application?->recordApiConnection($request->ip());
            }
        } elseif ($clientId) {
            $app = Application::where('client_id', $clientId)->where('status', 'active')->first();
            if ($app) {
                if ($clientSecret && ($clientSecret === $app->client_secret || (is_string($app->client_secret) && Hash::check((string) $clientSecret, $app->client_secret)))) {
                    $isAuthenticatedApp = true;
                }
                $app->recordApiConnection($request->ip());
            }
        }

        $query = User::where('role', 'alumni')->with('jurusan');

        // Filter berdasarkan kode jurusan (PPL, TO, AKL, PM, MPLB) baik dari query atau route param
        $jurusanKode = $kode ?: $request->query('jurusan');
        if ($jurusanKode && $jurusanKode !== 'all') {
            $normalized = Jurusan::extractKodeJurusan($jurusanKode) ?: strtoupper(trim($jurusanKode));
            $query->whereHas('jurusan', function ($q) use ($normalized) {
                $q->where('kode_jurusan', $normalized);
            });
        }

        // Filter berdasarkan Tahun Masuk (dibuat siswa / created_at)
        $tahunMasuk = $request->query('tahun_masuk') ?: $request->query('angkatan');
        if ($tahunMasuk && is_numeric($tahunMasuk)) {
            $query->whereYear('created_at', (int) $tahunMasuk);
        }

        // Filter berdasarkan Tahun Lulus (diperbarui status kelulusan / updated_at)
        $tahunLulus = $request->query('tahun_lulus') ?: $request->query('lulus');
        if ($tahunLulus && is_numeric($tahunLulus)) {
            $query->whereYear('updated_at', (int) $tahunLulus);
        }

        // Pencarian nama, email, NIS, atau kelas
        $search = trim((string) $request->query('search', ''));
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('classroom', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $paginated = $query->orderByRaw("COALESCE(classroom, '') ASC, name ASC")->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'data' => $paginated->map(function ($u) use ($isAuthenticatedApp) {
                return [
                    'id' => (string) $u->id,
                    'external_id' => $u->external_id,
                    'nis' => $u->nis,
                    'name' => $u->name,
                    'email' => $isAuthenticatedApp ? $u->email : $this->maskEmail($u->email),
                    'phone' => $isAuthenticatedApp ? $u->phone : $this->maskPhone($u->phone),
                    'avatar_url' => $u->avatar_url,
                    'avatar' => $u->avatar_url,
                    'role' => $u->role,
                    'classroom' => $u->classroom,
                    'jurusan' => $u->jurusan ? [
                        'id' => $u->jurusan->id,
                        'kode_jurusan' => $u->jurusan->kode_jurusan,
                        'nama_jurusan' => $u->jurusan->nama_jurusan,
                    ] : null,
                    'kode_jurusan' => $u->jurusan?->kode_jurusan,
                    'nama_jurusan' => $u->jurusan?->nama_jurusan,
                    'tahun_masuk' => $u->tahun_masuk,
                    'tahun_lulus' => $u->tahun_lulus,
                    'created_at' => $u->created_at?->toIso8601String(),
                    'updated_at' => $u->updated_at?->toIso8601String(),
                    'status' => $u->status,
                ];
            }),
        ]);
    }

    /**
     * Endpoint API untuk aplikasi downstream mengambil data detail satu alumni berdasarkan NIS, External ID, atau ID
     */
    public function alumniDetail(Request $request, string $identifier): JsonResponse
    {
        $tokenString = $request->bearerToken();
        $clientId = $request->header('X-Client-ID') ?: $request->input('client_id');
        $clientSecret = $request->header('X-Client-Secret') ?: $request->input('client_secret');
        $isAuthenticatedApp = false;

        if ($tokenString) {
            $token = OAuthAccessToken::where('token', $tokenString)->where('revoked', false)->where('expires_at', '>', now())->first();
            if ($token) {
                $isAuthenticatedApp = true;
                $token->application?->recordApiConnection($request->ip());
            }
        } elseif ($clientId) {
            $app = Application::where('client_id', $clientId)->where('status', 'active')->first();
            if ($app) {
                if ($clientSecret && ($clientSecret === $app->client_secret || (is_string($app->client_secret) && Hash::check((string) $clientSecret, $app->client_secret)))) {
                    $isAuthenticatedApp = true;
                }
                $app->recordApiConnection($request->ip());
            }
        }

        $alumni = User::where('role', 'alumni')
            ->where(function ($q) use ($identifier) {
                $q->where('external_id', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('id', $identifier)
                    ->orWhere('email', $identifier);
            })
            ->with('jurusan')
            ->first();

        if (! $alumni) {
            return response()->json([
                'status' => 'error',
                'message' => "Data alumni dengan NIS/ID '{$identifier}' tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $alumni->id,
                'external_id' => $alumni->external_id,
                'nis' => $alumni->nis,
                'name' => $alumni->name,
                'email' => $isAuthenticatedApp ? $alumni->email : $this->maskEmail($alumni->email),
                'phone' => $isAuthenticatedApp ? $alumni->phone : $this->maskPhone($alumni->phone),
                'avatar_url' => $alumni->avatar_url,
                'avatar' => $alumni->avatar_url,
                'role' => $alumni->role,
                'classroom' => $alumni->classroom,
                'jurusan' => $alumni->jurusan ? [
                    'id' => $alumni->jurusan->id,
                    'kode_jurusan' => $alumni->jurusan->kode_jurusan,
                    'nama_jurusan' => $alumni->jurusan->nama_jurusan,
                ] : null,
                'kode_jurusan' => $alumni->jurusan?->kode_jurusan,
                'nama_jurusan' => $alumni->jurusan?->nama_jurusan,
                'tahun_masuk' => $alumni->tahun_masuk,
                'tahun_lulus' => $alumni->tahun_lulus,
                'created_at' => $alumni->created_at?->toIso8601String(),
                'updated_at' => $alumni->updated_at?->toIso8601String(),
                'status' => $alumni->status,
            ],
        ]);
    }

    /**
     * Mask email address for unauthenticated public requests to protect student/alumni privacy.
     */
    protected function maskEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $parts = explode('@', $email, 2);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1).'*';
        } elseif ($len <= 4) {
            $maskedName = substr($name, 0, 1).str_repeat('*', $len - 1);
        } else {
            $maskedName = substr($name, 0, 2).str_repeat('*', $len - 3).substr($name, -1);
        }

        return $domain ? "{$maskedName}@{$domain}" : $maskedName;
    }

    /**
     * Mask phone number for unauthenticated public requests to protect student/alumni privacy.
     */
    protected function maskPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $len = strlen($phone);
        if ($len >= 8) {
            return substr($phone, 0, 4).str_repeat('*', max(2, $len - 7)).substr($phone, -3);
        }

        return substr($phone, 0, 2).'****';
    }
}
