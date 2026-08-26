<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GatewayHealthValidationService;
use App\Services\SijunaApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiIdentityController extends Controller
{
    /**
     * Return primary identity object for the authenticated OAuth user
     */
    public function user(Request $request, \App\Services\PasswordSyncService $passwordSyncService): JsonResponse
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

        if ($user->phone) {
            $response['phone'] = $user->phone;
        }

        return response()->json($response);
    }

    /**
     * Return detailed profile data including role details, timestamps, and enriched SIJUNA data
     */
    public function profile(Request $request, SijunaApiService $sijunaService, \App\Services\PasswordSyncService $passwordSyncService): JsonResponse
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
            'roles' => $user->roles->pluck('name'),
            'sijuna_data' => $sijunaData,
            'accessed_via_app' => $app ? [
                'name' => $app->name,
                'client_id' => $app->client_id,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
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
    public function passwordSync(Request $request, \App\Services\PasswordSyncService $passwordSyncService): JsonResponse
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
}
