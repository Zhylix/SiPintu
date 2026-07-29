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
     * Gateway Proxy API: Retrieve students data from SIJUNA (with Redis caching)
     */
    public function students(Request $request, SijunaApiService $sijunaService): JsonResponse
    {
        $students = $sijunaService->getStudents();

        return response()->json([
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache)',
            'count' => count($students),
            'data' => $students,
        ]);
    }

    /**
     * Gateway Proxy API: Retrieve specific student data from SIJUNA by External ID
     */
    public function studentDetail(Request $request, string $externalId, SijunaApiService $sijunaService): JsonResponse
    {
        $student = $sijunaService->getStudentByExternalId($externalId);

        if (! $student) {
            return response()->json([
                'error' => 'not_found',
                'message' => "Data siswa dengan ID {$externalId} tidak ditemukan di database SIJUNA.",
            ], 404);
        }

        return response()->json([
            'source' => 'Gateway Proxy (SIJUNA Service + Redis Cache)',
            'data' => $student,
        ]);
    }
}
