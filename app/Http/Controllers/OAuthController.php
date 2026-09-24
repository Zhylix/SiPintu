<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthRefreshToken;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PasswordSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * OAuth 2.0 / OIDC Authorization Endpoint
     */
    public function authorize(Request $request)
    {
        $clientId = $request->query('client_id');
        $redirectUri = $request->query('redirect_uri');
        $responseType = $request->query('response_type', 'code');
        $scope = $request->query('scope', 'openid profile email');
        $state = $request->query('state');
        $codeChallenge = $request->query('code_challenge');
        $codeChallengeMethod = $request->query('code_challenge_method');

        // 1. Validate Client Application
        $application = Application::where('client_id', $clientId)->first();
        if (! $application || $application->status !== 'active') {
            AuditLogger::log('sso_authorize_invalid_client', [
                'client_id' => $clientId,
                'via_sso' => true,
                'is_sso_failure' => true,
            ]);

            return response()->view('oauth.error', [
                'title' => 'Client Aplikasi Tidak Valid',
                'message' => 'Aplikasi eksternal dengan client_id tersebut tidak ditemukan atau sedang dinonaktifkan.',
            ], 400);
        }

        // 2. Validate Redirect URI
        $registeredUris = array_values(array_filter(array_map('trim', explode(',', $application->redirect_uri ?? ''))));
        $targetRedirectUri = null;

        if ($redirectUri) {
            $normalizedRequested = rtrim($redirectUri, '/');
            foreach ($registeredUris as $regUri) {
                $normalizedReg = rtrim($regUri, '/');
                if ($normalizedRequested === $normalizedReg || str_starts_with($redirectUri, $normalizedReg.'?') || str_starts_with($redirectUri, $normalizedReg.'#')) {
                    $targetRedirectUri = $redirectUri;
                    break;
                }
            }

            if (! $targetRedirectUri) {
                AuditLogger::log('sso_authorize_invalid_redirect', [
                    'client_id' => $clientId,
                    'requested_uri' => $redirectUri,
                    'via_sso' => true,
                    'is_sso_failure' => true,
                ]);

                return response()->view('oauth.error', [
                    'title' => 'Redirect URI Tidak Valid',
                    'message' => 'Redirect URI yang dikirimkan tidak sesuai dengan konfigurasi terdaftar di Gateway.',
                ], 400);
            }
        } else {
            $targetRedirectUri = $registeredUris[0] ?? $application->base_url;
        }

        // 3. Ensure User SSO Session is Authenticated
        if (! Auth::check()) {
            // Save full authorization request context in session so after login user returns seamlessly
            session()->put('oauth_return_to', $request->fullUrl());

            return redirect()->route('login')->with('info', 'Silakan login di SiPintu untuk melanjutkan.');
        }

        $user = Auth::user();

        // 4. Verify Active Account Status
        if ($user->status !== 'active') {
            AuditLogger::log('sso_access_denied_inactive', [
                'application_id' => $application->id,
                'app_name' => $application->name,
                'user_id' => $user->id,
                'status' => $user->status,
                'via_sso' => true,
                'is_sso_failure' => true,
            ], $user->id);

            return response()->view('oauth.error', [
                'title' => 'Akun Dinonaktifkan atau Ditangguhkan',
                'message' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan. Silakan hubungi administrator sekolah untuk mengaktifkan kembali akun Anda.',
            ], 403);
        }

        // 5. Check Application Access Role Permission
        if (! $user->canAccessApplication($application)) {
            AuditLogger::log('sso_access_denied', [
                'application_id' => $application->id,
                'app_name' => $application->name,
                'user_id' => $user->id,
                'role' => $user->role,
                'via_sso' => true,
                'is_sso_failure' => true,
            ], $user->id);

            return response()->view('oauth.denied', [
                'user' => $user,
                'application' => $application,
            ], 403);
        }

        // 5. Generate Authorization Code
        $code = Str::random(64);
        OAuthAuthCode::create([
            'id' => $code,
            'user_id' => $user->id,
            'application_id' => $application->id,
            'redirect_uri' => $targetRedirectUri,
            'scopes' => $scope,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallenge ? ($codeChallengeMethod ?: 'S256') : null,
            'expires_at' => now()->addMinutes(5),
            'revoked' => false,
        ]);

        AuditLogger::log('sso_authorize_granted', [
            'application_id' => $application->id,
            'app_name' => $application->name,
            'user_id' => $user->id,
            'via_sso' => true,
        ], $user->id);

        // Build redirect URL
        $delimiter = str_contains($targetRedirectUri, '?') ? '&' : '?';
        $redirectUrl = $targetRedirectUri.$delimiter.http_build_query([
            'code' => $code,
            'state' => $state,
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * OAuth 2.0 Token Endpoint (/oauth/token)
     */
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        // Allow Basic Auth header for Client credentials
        if (! $clientId && $request->header('PHP_AUTH_USER')) {
            $clientId = $request->header('PHP_AUTH_USER');
            $clientSecret = $request->header('PHP_AUTH_PW');
        }

        $application = Application::where('client_id', $clientId)->first();
        if (! $application) {
            return response()->json(['error' => 'invalid_client', 'error_description' => 'Client ID not found.'], 401);
        }

        // Verify Client Secret safely (supports plaintext and bcrypt)
        $secretValid = ($clientSecret === $application->client_secret)
            || (is_string($application->client_secret) && str_starts_with($application->client_secret, '$2y$') && Hash::check($clientSecret, $application->client_secret));
        if (! $secretValid) {
            AuditLogger::log('token_exchange_invalid_secret', [
                'client_id' => $clientId,
                'via_sso' => true,
                'is_sso_failure' => true,
            ]);

            return response()->json(['error' => 'invalid_client', 'error_description' => 'Client secret verification failed.'], 401);
        }

        if ($grantType === 'authorization_code') {
            $codeStr = $request->input('code');
            $authCode = OAuthAuthCode::with('user')
                ->where('application_id', $application->id)
                ->where('id', $codeStr)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if (! $authCode) {
                AuditLogger::log('token_exchange_invalid_code', [
                    'client_id' => $clientId,
                    'via_sso' => true,
                    'is_sso_failure' => true,
                ]);

                return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code is invalid, expired, or revoked.'], 400);
            }

            // Verify PKCE (RFC 7636) if code_challenge was provided
            if (! empty($authCode->code_challenge)) {
                $codeVerifier = $request->input('code_verifier');
                if (empty($codeVerifier)) {
                    AuditLogger::log('token_exchange_missing_pkce_verifier', [
                        'client_id' => $clientId,
                        'via_sso' => true,
                        'is_sso_failure' => true,
                    ]);

                    return response()->json([
                        'error' => 'invalid_request',
                        'error_description' => 'PKCE verification failed: code_verifier is required.',
                    ], 400);
                }

                $method = strtoupper((string) ($authCode->code_challenge_method ?: 'S256'));
                $isValidPkce = false;

                if ($method === 'S256') {
                    $rawHash = hash('sha256', (string) $codeVerifier, true);
                    $calculatedChallenge = rtrim(strtr(base64_encode($rawHash), '+/', '-_'), '=');
                    $isValidPkce = hash_equals($authCode->code_challenge, $calculatedChallenge);
                } elseif ($method === 'PLAIN') {
                    $isValidPkce = hash_equals($authCode->code_challenge, (string) $codeVerifier);
                }

                if (! $isValidPkce) {
                    AuditLogger::log('token_exchange_invalid_pkce_verifier', [
                        'client_id' => $clientId,
                        'via_sso' => true,
                        'is_sso_failure' => true,
                    ]);

                    return response()->json([
                        'error' => 'invalid_grant',
                        'error_description' => 'PKCE verification failed: code_verifier does not match code_challenge.',
                    ], 400);
                }
            }

            // Revoke authorization code immediately (single-use)
            $authCode->update(['revoked' => true]);

            $user = $authCode->user;

            if ($user->status !== 'active') {
                AuditLogger::log('token_exchange_inactive_user', [
                    'client_id' => $clientId,
                    'user_id' => $user->id,
                    'status' => $user->status,
                    'via_sso' => true,
                    'is_sso_failure' => true,
                ], $user->id);

                return response()->json([
                    'error' => 'invalid_grant',
                    'error_description' => 'Akun pengguna sedang dinonaktifkan atau ditangguhkan.',
                ], 403);
            }

            $accessTokenStr = Str::random(80);
            $refreshTokenStr = Str::random(80);
            $accessTokenId = (string) Str::uuid();

            $accessToken = OAuthAccessToken::create([
                'id' => $accessTokenId,
                'user_id' => $user->id,
                'application_id' => $application->id,
                'token' => $accessTokenStr,
                'scopes' => $authCode->scopes ?: 'openid profile email',
                'expires_at' => now()->addHours(24),
                'revoked' => false,
            ]);

            OAuthRefreshToken::create([
                'id' => (string) Str::uuid(),
                'access_token_id' => $accessTokenId,
                'token' => $refreshTokenStr,
                'expires_at' => now()->addDays(30),
                'revoked' => false,
            ]);

            // Generate OIDC ID Token (JWT)
            $idToken = $this->generateIdToken($user, $application);

            AuditLogger::log('token_exchange_success', [
                'application_id' => $application->id,
                'user_id' => $user->id,
            ], $user->id);

            return response()->json(array_merge([
                'access_token' => $accessTokenStr,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'refresh_token' => $refreshTokenStr,
                'id_token' => $idToken,
                'scope' => $authCode->scopes ?: 'openid profile email',
            ], app(PasswordSyncService::class)->getPasswordPayload($user)));
        }

        if ($grantType === 'refresh_token') {
            $refreshTokenStr = $request->input('refresh_token');
            $refreshToken = OAuthRefreshToken::with('accessToken.user')
                ->where('token', $refreshTokenStr)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if (! $refreshToken || ! $refreshToken->accessToken) {
                return response()->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token is invalid or expired.'], 400);
            }

            // Revoke old refresh token & access token
            $refreshToken->update(['revoked' => true]);
            $refreshToken->accessToken->update(['revoked' => true]);

            $user = $refreshToken->accessToken->user;

            if ($user->status !== 'active') {
                return response()->json([
                    'error' => 'invalid_grant',
                    'error_description' => 'Akun pengguna sedang dinonaktifkan atau ditangguhkan.',
                ], 403);
            }

            $newAccessTokenStr = Str::random(80);
            $newRefreshTokenStr = Str::random(80);
            $newAccessTokenId = (string) Str::uuid();

            OAuthAccessToken::create([
                'id' => $newAccessTokenId,
                'user_id' => $user->id,
                'application_id' => $application->id,
                'token' => $newAccessTokenStr,
                'scopes' => $refreshToken->accessToken->scopes,
                'expires_at' => now()->addHours(24),
                'revoked' => false,
            ]);

            OAuthRefreshToken::create([
                'id' => (string) Str::uuid(),
                'access_token_id' => $newAccessTokenId,
                'token' => $newRefreshTokenStr,
                'expires_at' => now()->addDays(30),
                'revoked' => false,
            ]);

            $idToken = $this->generateIdToken($user, $application);

            return response()->json(array_merge([
                'access_token' => $newAccessTokenStr,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'refresh_token' => $newRefreshTokenStr,
                'id_token' => $idToken,
                'scope' => $refreshToken->accessToken->scopes,
            ], app(PasswordSyncService::class)->getPasswordPayload($user)));
        }

        return response()->json(['error' => 'unsupported_grant_type', 'error_description' => 'Grant type not supported.'], 400);
    }

    /**
     * OAuth 2.0 / OIDC Revocation & End Session Endpoint (/oauth/logout)
     */
    public function logout(Request $request): JsonResponse
    {
        $tokenString = $request->bearerToken()
            ?: $request->input('token')
            ?: $request->input('access_token')
            ?: $request->input('refresh_token');

        $revokedCount = 0;

        if ($tokenString) {
            $accessToken = OAuthAccessToken::where('token', $tokenString)->first();
            if ($accessToken) {
                $accessToken->update(['revoked' => true]);
                OAuthRefreshToken::where('access_token_id', $accessToken->id)->update(['revoked' => true]);
                $revokedCount++;

                AuditLogger::log('oauth_logout_token_revoked', [
                    'token_id' => $accessToken->id,
                    'user_id' => $accessToken->user_id,
                    'application_id' => $accessToken->application_id,
                ], $accessToken->user_id);
            }

            $refreshToken = OAuthRefreshToken::with('accessToken')->where('token', $tokenString)->first();
            if ($refreshToken) {
                $refreshToken->update(['revoked' => true]);
                if ($refreshToken->accessToken) {
                    $refreshToken->accessToken->update(['revoked' => true]);
                }
                $revokedCount++;
            }
        }

        if ($request->hasSession()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out and revoked credentials.',
            'revoked' => $revokedCount > 0,
        ]);
    }

    /**
     * OpenID Connect Discovery Metadata Endpoint
     */
    public function openidConfiguration(): JsonResponse
    {
        $baseUrl = config('app.url', 'http://localhost:8000');

        return response()->json([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl.'/oauth/authorize',
            'token_endpoint' => $baseUrl.'/oauth/token',
            'userinfo_endpoint' => $baseUrl.'/api/v1/user',
            'end_session_endpoint' => $baseUrl.'/oauth/logout',
            'jwks_uri' => $baseUrl.'/oauth/jwks.json',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['HS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'claims_supported' => ['sub', 'iss', 'name', 'email', 'role', 'external_id', 'password_change_policy', 'password_sync_required'],
        ]);
    }

    /**
     * JSON Web Key Set Endpoint
     */
    public function jwks(): JsonResponse
    {
        return response()->json([
            'keys' => [
                [
                    'kty' => 'oct',
                    'alg' => 'HS256',
                    'use' => 'sig',
                    'kid' => 'gateway-key-1',
                ],
            ],
        ]);
    }

    /**
     * Helper to create HMAC JWT ID Token for OpenID Connect
     */
    protected function generateIdToken($user, $application): string
    {
        $base64UrlEncode = fn ($data) => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(is_string($data) ? $data : json_encode($data)));

        $header = $base64UrlEncode(['alg' => 'HS256', 'typ' => 'JWT']);

        $primaryRole = $user->roles->first()?->slug ?? $user->role;

        $passwordSync = app(PasswordSyncService::class)->getPasswordPayload($user);

        $payload = $base64UrlEncode(array_merge([
            'iss' => config('app.url', 'http://localhost:8000'),
            'sub' => (string) $user->id,
            'aud' => $application->client_id,
            'iat' => time(),
            'exp' => time() + 86400,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'role' => $primaryRole,
            'status' => $user->status,
            'phone' => $user->phone,
            'phone_number' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'picture' => $user->avatar_url,
            'external_id' => $user->external_id,
            'classroom' => $user->classroom,
            'jurusan_id' => $user->jurusan_id,
            'kode_jurusan' => $user->jurusan?->kode_jurusan,
            'nama_jurusan' => $user->jurusan?->nama_jurusan,
            'tahun_masuk' => $user->tahun_masuk,
            'tahun_lulus' => $user->isAlumni() ? $user->tahun_lulus : null,
        ], $passwordSync));

        $signatureKey = config('app.key', 'secret_gateway_key');
        $signature = hash_hmac('sha256', "{$header}.{$payload}", $signatureKey, true);
        $encodedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "{$header}.{$payload}.{$encodedSignature}";
    }

    /**
     * Webhook receiver: Synchronize user profile from SiPintu SSO with local conflict resolution.
     */
    public function syncUser(Request $request): JsonResponse
    {
        // 1. Security: HMAC SHA-256 Signature Verification
        $signature = $request->header('X-SiPintu-Signature');
        $clientSecret = config('services.sipintu.client_secret') ?: env('SIPINTU_CLIENT_SECRET');

        if ($clientSecret) {
            if (! $signature) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing X-SiPintu-Signature header.',
                ], 401);
            }

            $computed = hash_hmac('sha256', $request->getContent(), $clientSecret);
            if (! hash_equals($computed, $signature)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid signature.',
                ], 401);
            }
        }

        // 2. Extract and Normalize Payload
        $userData = $request->input('user');
        if (! is_array($userData)) {
            $userData = $request->all();
        }
        if (isset($userData['user']) && is_array($userData['user'])) {
            $userData = $userData['user'];
        }

        $previous = $request->input('previous', []);

        if (! is_array($userData) || empty($userData['email'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid user payload: email is required.',
            ], 400);
        }

        // 3. Locate User by external_id, new email, or previous email
        $user = null;
        if (! empty($userData['external_id'])) {
            $user = User::where('external_id', $userData['external_id'])->first();
        }

        if (! $user && ! empty($userData['email'])) {
            $user = User::where('email', $userData['email'])
                ->when(! empty($previous['email']), function ($q) use ($previous) {
                    $q->orWhere('email', $previous['email']);
                })
                ->first();
        }

        $syncTime = now();

        // 4. If User does not exist locally, create new user with all data from SiPintu (Rule 7)
        if (! $user) {
            $createFields = [
                'name' => $userData['name'] ?? 'User',
                'email' => $userData['email'],
                'role' => $userData['role'] ?? 'student',
                'status' => $userData['status'] ?? 'active',
                'email_verified_at' => $syncTime,
                'sipintu_last_synced_at' => $syncTime,
            ];

            if (! empty($userData['password'])) {
                $createFields['password'] = $userData['password'];
            } else {
                $createFields['password'] = Str::random(32);
            }

            if (isset($userData['classroom'])) {
                $createFields['classroom'] = $userData['classroom'];
            }
            if (isset($userData['phone'])) {
                $createFields['phone'] = $userData['phone'];
            }
            if (isset($userData['username'])) {
                $createFields['username'] = $userData['username'];
            }
            if (isset($userData['external_id'])) {
                $createFields['external_id'] = $userData['external_id'];
            }
            if (isset($userData['avatar_url']) || isset($userData['avatar'])) {
                $avatarVal = $userData['avatar_url'] ?? $userData['avatar'];
                if (Schema::hasColumn('users', 'avatar_url')) {
                    $createFields['avatar_url'] = $avatarVal;
                }
                if (Schema::hasColumn('users', 'avatar')) {
                    $createFields['avatar'] = $avatarVal;
                }
            }

            $user = new User;
            $user->fill($createFields);
            $user->created_at = $syncTime;
            $user->updated_at = $syncTime;
            $user->sipintu_last_synced_at = $syncTime;
            $user->save();

            $appliedFields = array_values(array_unique(array_merge(
                ['email', 'role', 'status'],
                isset($userData['name']) ? ['name'] : [],
                isset($userData['phone']) ? ['phone'] : [],
                isset($userData['classroom']) ? ['classroom'] : [],
                (isset($userData['avatar_url']) || isset($userData['avatar'])) ? ['avatar_url'] : [],
                ! empty($userData['password']) ? ['password'] : []
            )));
            $skippedFields = [];

            Log::info('SiPintu webhook user sync: user created', [
                'user_id' => $user->id,
                'updated_fields' => $appliedFields,
                'skipped_fields' => $skippedFields,
            ]);

            return response()->json([
                'status' => 'success',
                'action' => 'created',
                'message' => "User {$user->email} berhasil disinkronkan dan dibuat.",
                'user_id' => $user->id,
                'updated_fields' => $appliedFields,
                'skipped_fields' => $skippedFields,
                'sipintu_last_synced_at' => $syncTime->toIso8601String(),
            ]);
        }

        // 5. Check if user has local edits since last sync (Rule 3)
        // - sipintu_last_synced_at == null -> overwrite all
        // - updated_at > sipintu_last_synced_at -> user edited profile locally after last sync -> DO NOT overwrite local fields
        // - updated_at <= sipintu_last_synced_at -> no local edits since last sync -> safe to overwrite with SiPintu data
        $hasLocalEdits = false;
        if ($user->sipintu_last_synced_at === null) {
            $hasLocalEdits = false;
        } elseif ($user->updated_at && $user->updated_at->gt($user->sipintu_last_synced_at)) {
            $hasLocalEdits = true;
        }

        $updateFields = [];
        $appliedFields = [];
        $skippedFields = [];

        // 6. Fields that ALWAYS follow SiPintu (Rule 2: email, role, status, password)
        $updateFields['email'] = $userData['email'];
        $appliedFields[] = 'email';

        if (isset($userData['role'])) {
            $updateFields['role'] = $userData['role'];
            $appliedFields[] = 'role';
        }

        if (isset($userData['status'])) {
            $updateFields['status'] = $userData['status'];
            $appliedFields[] = 'status';
        }

        if (! empty($userData['password'])) {
            $updateFields['password'] = $userData['password'];
            $appliedFields[] = 'password';
        }

        // 7. Fields protected against local edits (Rule 3: name, phone, classroom, avatar_url)
        $candidateLocalFields = [
            'name' => $userData['name'] ?? null,
            'phone' => $userData['phone'] ?? null,
            'classroom' => $userData['classroom'] ?? null,
        ];

        foreach ($candidateLocalFields as $field => $val) {
            if ($val !== null) {
                if ($hasLocalEdits) {
                    $skippedFields[] = $field;
                } else {
                    $updateFields[$field] = $val;
                    $appliedFields[] = $field;
                }
            }
        }

        if (isset($userData['avatar_url']) || isset($userData['avatar'])) {
            $avatarVal = $userData['avatar_url'] ?? $userData['avatar'];
            if ($hasLocalEdits) {
                $skippedFields[] = 'avatar_url';
            } else {
                if (Schema::hasColumn('users', 'avatar_url')) {
                    $updateFields['avatar_url'] = $avatarVal;
                }
                if (Schema::hasColumn('users', 'avatar')) {
                    $updateFields['avatar'] = $avatarVal;
                }
                $appliedFields[] = 'avatar_url';
            }
        }

        // Keep external_id / username synced if provided
        if (isset($userData['external_id']) && Schema::hasColumn('users', 'external_id')) {
            $updateFields['external_id'] = $userData['external_id'];
        }
        if (isset($userData['username']) && Schema::hasColumn('users', 'username')) {
            $updateFields['username'] = $userData['username'];
        }

        // 8. Update user and ensure updated_at does not exceed sipintu_last_synced_at (Rule 4)
        $updateFields['sipintu_last_synced_at'] = $syncTime;

        $user->fill($updateFields);
        $user->sipintu_last_synced_at = $syncTime;
        $user->updated_at = $syncTime;
        $user->save();

        // 9. Logging (Rule 6)
        Log::info('SiPintu webhook user sync: user updated', [
            'user_id' => $user->id,
            'updated_fields' => $appliedFields,
            'skipped_fields' => $skippedFields,
            'has_local_edits' => $hasLocalEdits,
        ]);

        return response()->json([
            'status' => 'success',
            'action' => 'updated',
            'message' => "User {$user->email} berhasil disinkronkan.",
            'user_id' => $user->id,
            'updated_fields' => $appliedFields,
            'skipped_fields' => $skippedFields,
            'sipintu_last_synced_at' => $syncTime->toIso8601String(),
        ]);
    }
}
