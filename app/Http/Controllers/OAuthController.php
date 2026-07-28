<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthRefreshToken;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // 1. Validate Client Application
        $application = Application::where('client_id', $clientId)->first();
        if (!$application || $application->status !== 'active') {
            AuditLogger::log('sso_authorize_invalid_client', ['client_id' => $clientId]);
            return response()->view('oauth.error', [
                'title' => 'Client Aplikasi Tidak Valid',
                'message' => 'Aplikasi eksternal dengan client_id tersebut tidak ditemukan atau sedang dinonaktifkan.',
            ], 400);
        }

        // 2. Validate Redirect URI
        if ($redirectUri && !str_starts_with($redirectUri, rtrim($application->redirect_uri, '/'))) {
            AuditLogger::log('sso_authorize_invalid_redirect', [
                'client_id' => $clientId,
                'requested_uri' => $redirectUri,
            ]);
            return response()->view('oauth.error', [
                'title' => 'Redirect URI Tidak Valid',
                'message' => 'Redirect URI yang dikirimkan tidak sesuai dengan konfigurasi terdaftar di Gateway.',
            ], 400);
        }

        // Default redirect URI if omitted
        $targetRedirectUri = $redirectUri ?: explode(',', $application->redirect_uri)[0];

        // 3. Ensure User SSO Session is Authenticated
        if (!Auth::check()) {
            // Save full authorization request context in session so after login user returns seamlessly
            session()->put('oauth_return_to', $request->fullUrl());
            return redirect()->route('login')->with('info', 'Silakan login di Central Identity Gateway untuk melanjutkan.');
        }

        $user = Auth::user();

        // 4. Check Application Access Role Permission
        if (!$user->canAccessApplication($application)) {
            AuditLogger::log('sso_access_denied', [
                'application_id' => $application->id,
                'app_name' => $application->name,
                'user_id' => $user->id,
                'role' => $user->user_type,
            ], $user->id);

            return response()->view('oauth.denied', [
                'user' => $user,
                'application' => $application,
            ], 403);
        }

        // 5. Generate Authorization Code
        $code = Str::random(64);
        OAuthAuthCode::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'application_id' => $application->id,
            'code' => $code,
            'redirect_uri' => $targetRedirectUri,
            'scopes' => $scope,
            'expires_at' => now()->addMinutes(5),
            'revoked' => false,
        ]);

        AuditLogger::log('sso_authorize_granted', [
            'application_id' => $application->id,
            'app_name' => $application->name,
            'user_id' => $user->id,
        ], $user->id);

        // Build redirect URL
        $delimiter = str_contains($targetRedirectUri, '?') ? '&' : '?';
        $redirectUrl = $targetRedirectUri . $delimiter . http_build_query([
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
        if (!$clientId && $request->header('PHP_AUTH_USER')) {
            $clientId = $request->header('PHP_AUTH_USER');
            $clientSecret = $request->header('PHP_AUTH_PW');
        }

        $application = Application::where('client_id', $clientId)->first();
        if (!$application) {
            return response()->json(['error' => 'invalid_client', 'error_description' => 'Client ID not found.'], 401);
        }

        // Verify Client Secret
        $secretValid = Hash::check($clientSecret, $application->client_secret) || $clientSecret === $application->client_secret;
        if (!$secretValid) {
            AuditLogger::log('token_exchange_invalid_secret', ['client_id' => $clientId]);
            return response()->json(['error' => 'invalid_client', 'error_description' => 'Client secret verification failed.'], 401);
        }

        if ($grantType === 'authorization_code') {
            $codeStr = $request->input('code');
            $authCode = OAuthAuthCode::with('user')
                ->where('application_id', $application->id)
                ->where('code', $codeStr)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$authCode) {
                AuditLogger::log('token_exchange_invalid_code', ['client_id' => $clientId]);
                return response()->json(['error' => 'invalid_grant', 'error_description' => 'Authorization code is invalid, expired, or revoked.'], 400);
            }

            // Revoke authorization code immediately (single-use)
            $authCode->update(['revoked' => true]);

            $user = $authCode->user;
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

            return response()->json([
                'access_token' => $accessTokenStr,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'refresh_token' => $refreshTokenStr,
                'id_token' => $idToken,
                'scope' => $authCode->scopes ?: 'openid profile email',
            ]);
        }

        if ($grantType === 'refresh_token') {
            $refreshTokenStr = $request->input('refresh_token');
            $refreshToken = OAuthRefreshToken::with('accessToken.user')
                ->where('token', $refreshTokenStr)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$refreshToken || !$refreshToken->accessToken) {
                return response()->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token is invalid or expired.'], 400);
            }

            // Revoke old refresh token & access token
            $refreshToken->update(['revoked' => true]);
            $refreshToken->accessToken->update(['revoked' => true]);

            $user = $refreshToken->accessToken->user;
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

            return response()->json([
                'access_token' => $newAccessTokenStr,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'refresh_token' => $newRefreshTokenStr,
                'id_token' => $idToken,
                'scope' => $refreshToken->accessToken->scopes,
            ]);
        }

        return response()->json(['error' => 'unsupported_grant_type', 'error_description' => 'Grant type not supported.'], 400);
    }

    /**
     * OpenID Connect Discovery Metadata Endpoint
     */
    public function openidConfiguration(): JsonResponse
    {
        $baseUrl = config('app.url', 'http://localhost:8000');

        return response()->json([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/oauth/authorize',
            'token_endpoint' => $baseUrl . '/oauth/token',
            'userinfo_endpoint' => $baseUrl . '/api/v1/user',
            'end_session_endpoint' => $baseUrl . '/oauth/logout',
            'jwks_uri' => $baseUrl . '/oauth/jwks.json',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['HS256'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'claims_supported' => ['sub', 'iss', 'name', 'email', 'role', 'external_id'],
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
                ]
            ]
        ]);
    }

    /**
     * Helper to create HMAC JWT ID Token for OpenID Connect
     */
    protected function generateIdToken($user, $application): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        
        $primaryRole = $user->roles->first()?->slug ?? $user->user_type;

        $payload = base64_encode(json_encode([
            'iss' => config('app.url', 'http://localhost:8000'),
            'sub' => (string) $user->id,
            'aud' => $application->client_id,
            'iat' => time(),
            'exp' => time() + 86400,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole,
            'external_id' => $user->external_id,
        ]));

        $signatureKey = config('app.key', 'secret_gateway_key');
        $signature = hash_hmac('sha256', "{$header}.{$payload}", $signatureKey, true);
        $encodedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "{$header}.{$payload}.{$encodedSignature}";
    }
}
