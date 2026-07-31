<?php

namespace App\Http\Middleware;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class OAuthBearerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check for Bearer Access Token
        $tokenString = $request->bearerToken();

        if ($tokenString) {
            $accessToken = OAuthAccessToken::with(['user', 'application'])
                ->where('token', $tokenString)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if ($accessToken && $accessToken->user) {
                $request->attributes->set('oauth_user', $accessToken->user);
                $request->attributes->set('oauth_token', $accessToken);
                $request->attributes->set('oauth_application', $accessToken->application);

                return $next($request);
            }
        }

        // 2. Check for X-Client-ID & X-Client-Secret (Server-to-Server Gateway Authentication)
        $clientId = $request->header('X-Client-ID') ?: $request->input('client_id');
        $clientSecret = $request->header('X-Client-Secret') ?: $request->input('client_secret');

        if ($clientId && $clientSecret) {
            $application = Application::where('client_id', $clientId)
                ->where('status', 'active')
                ->first();

            if ($application) {
                $secretValid = ($clientSecret === $application->client_secret);
                if (! $secretValid && (str_starts_with($application->client_secret, '$2y$') || str_starts_with($application->client_secret, '$2a$'))) {
                    try {
                        $secretValid = Hash::check($clientSecret, $application->client_secret);
                    } catch (\Throwable $e) {
                        $secretValid = false;
                    }
                }

                if ($secretValid) {
                    $request->attributes->set('oauth_application', $application);

                    return $next($request);
                }
            }
        }

        return response()->json([
            'error' => 'unauthorized',
            'message' => 'Missing or invalid credentials. Provide OAuth Bearer token or X-Client-ID and X-Client-Secret headers.',
        ], 401);
    }
}
