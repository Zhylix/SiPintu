<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SijunaApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiIdentityController extends Controller
{
    /**
     * Return primary identity object for the authenticated OAuth user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');

        $primaryRole = $user->roles->first()?->slug ?? $user->role;

        $response = [
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole,
        ];

        if ($user->phone) {
            $response['phone'] = $user->phone;
        }

        return response()->json($response);
    }

    /**
     * Return detailed profile data including role details, timestamps, and enriched SIJUNA data
     */
    public function profile(Request $request, SijunaApiService $sijunaService): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');
        $app = $request->attributes->get('oauth_application');

        $sijunaData = null;
        if ($user->external_id) {
            $sijunaData = $sijunaService->getStudentByExternalId($user->external_id);
        }

        return response()->json([
            'id' => (string) $user->id,
            'external_id' => $user->external_id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'user_type' => $user->role,
            'status' => $user->status,
            'roles' => $user->roles->pluck('slug'),
            'sijuna_data' => $sijunaData,
            'accessed_via_app' => $app ? [
                'name' => $app->name,
                'client_id' => $app->client_id,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Return list of roles and permissions for user
     */
    public function roles(Request $request): JsonResponse
    {
        $user = $request->attributes->get('oauth_user');

        return response()->json([
            'user_id' => (string) $user->id,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ];
            }),
            'permissions' => $user->permissions()->pluck('slug')->values(),
        ]);
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
                $localUser = \App\Models\User::where('username', $nis)
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
            $localUser = \App\Models\User::where('username', $externalId)
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
}
