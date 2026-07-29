<?php

namespace App\Http\Middleware;

use App\Models\OAuthAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuthBearerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenString = $request->bearerToken();

        if (! $tokenString) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Missing Bearer Access Token in Authorization header.',
            ], 401);
        }

        $accessToken = OAuthAccessToken::with(['user', 'application'])
            ->where('token', $tokenString)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $accessToken || ! $accessToken->user) {
            return response()->json([
                'error' => 'invalid_token',
                'message' => 'The provided access token is invalid, revoked, or expired.',
            ], 401);
        }

        // Attach authenticated OAuth user & client application to request attributes
        $request->attributes->set('oauth_user', $accessToken->user);
        $request->attributes->set('oauth_token', $accessToken);
        $request->attributes->set('oauth_application', $accessToken->application);

        return $next($request);
    }
}
