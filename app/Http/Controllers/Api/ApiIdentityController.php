<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\User;
use App\Services\GatewayHealthValidationService;
use App\Services\PasswordSyncService;
use App\Services\SijunaApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole,
        ], $passwordSyncService->getPasswordPayload($user));

        if ($user->classroom) {
            $response['classroom'] = $user->classroom;
        }

        if ($user->jurusan) {
            $response['jurusan_id'] = $user->jurusan->id;
            $response['kode_jurusan'] = $user->jurusan->kode_jurusan;
            $response['nama_jurusan'] = $user->jurusan->nama_jurusan;
            $response['jurusan'] = [
                'id' => $user->jurusan->id,
                'kode_jurusan' => $user->jurusan->kode_jurusan,
                'nama_jurusan' => $user->jurusan->nama_jurusan,
            ];
        }

        if ($user->phone) {
            $response['phone'] = $user->phone;
        }

        if ($user->isAlumni() || $user->role === 'alumni') {
            $response['tahun_masuk'] = $user->tahun_masuk;
            $response['tahun_lulus'] = $user->tahun_lulus;
        }

        $response['created_at'] = $user->created_at?->toIso8601String();
        $response['updated_at'] = $user->updated_at?->toIso8601String();

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
            } else {
                $sijunaData = $sijunaService->getStudentByExternalId($user->external_id ?: $user->username ?: $user->email);
            }
        }

        return response()->json(array_merge([
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
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
            'tahun_lulus' => $user->tahun_lulus,
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

        $identifier = $request->input('email') ?: $request->input('external_id') ?: $request->input('user_id');
        if ($identifier) {
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

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache)',
            'count' => count($students),
            'data' => $students,
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
                    'role' => $localUser->role,
                    'status' => $localUser->status,
                ];
            }
        }

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => "Data siswa dengan ID/NIS {$externalId} tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache + DB Fallback)',
            'data' => $student,
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
    public function alumni(Request $request): JsonResponse
    {
        $query = User::where('role', 'alumni')->with('jurusan');

        // Filter berdasarkan kode jurusan (PPL, TO, AKL, PM, MPLB)
        $jurusanKode = $request->query('jurusan');
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
            'data' => $paginated->map(function ($u) {
                return [
                    'id' => (string) $u->id,
                    'external_id' => $u->external_id,
                    'nis' => $u->nis,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
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
}

