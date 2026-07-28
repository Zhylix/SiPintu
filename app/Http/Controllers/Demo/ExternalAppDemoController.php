<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExternalAppDemoController extends Controller
{
    /**
     * Simulate opening external application (e.g., Aplikasi PKL) directly
     */
    public function index(Request $request, string $appSlug = 'pkl')
    {
        $app = $this->getDemoApplication($appSlug);
        $localSession = session()->get("demo_session_{$appSlug}");

        return view('demo.external_app', [
            'appSlug' => $appSlug,
            'app' => $app,
            'localSession' => $localSession,
            'activeStep' => $request->query('step', 1),
        ]);
    }

    /**
     * External App initiates redirect to Gateway OAuth SSO (/oauth/authorize)
     */
    public function loginRedirect(Request $request, string $appSlug = 'pkl'): RedirectResponse
    {
        $app = $this->getDemoApplication($appSlug);
        $state = Str::random(16);
        session()->put("demo_state_{$appSlug}", $state);

        $callbackUrl = $app->redirect_uri ?: route('demo.callback', ['appSlug' => $appSlug]);

        $gatewayAuthorizeUrl = route('oauth.authorize', [
            'client_id' => $app->client_id,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return redirect()->away($gatewayAuthorizeUrl);
    }

    /**
     * External App handles Callback from Gateway SSO (/demo/{appSlug}/callback?code=...&state=...)
     */
    public function callback(Request $request, string $appSlug = 'pkl')
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        $app = $this->getDemoApplication($appSlug);
        $localSession = session()->get("demo_session_{$appSlug}");

        if ($error) {
            return view('demo.external_app', [
                'appSlug' => $appSlug,
                'app' => $app,
                'localSession' => $localSession,
                'error' => "SSO Authentication Failed: {$error}",
            ]);
        }

        // 1. Exchange authorization code for tokens via POST /oauth/token
        try {
            $callbackUrl = $app->redirect_uri ?: route('demo.callback', ['appSlug' => $appSlug]);

            $tokenResponse = Http::post(route('oauth.token'), [
                'grant_type' => 'authorization_code',
                'client_id' => $app->client_id,
                'client_secret' => 'secret_' . $appSlug . '_12345', // Loaded from app client config
                'code' => $code,
                'redirect_uri' => $callbackUrl,
            ]);

            if (!$tokenResponse->successful()) {
                return view('demo.external_app', [
                    'appSlug' => $appSlug,
                    'app' => $app,
                    'localSession' => $localSession,
                    'error' => "Token Exchange Failed: " . $tokenResponse->body(),
                ]);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // 2. Fetch User Identity & Enriched SIJUNA Profile from Gateway API GET /api/v1/user/profile
            $userResponse = Http::withToken($accessToken)
                ->get(route('api.v1.user.profile'));

            if (!$userResponse->successful()) {
                return view('demo.external_app', [
                    'appSlug' => $appSlug,
                    'app' => $app,
                    'localSession' => $localSession,
                    'error' => "User Profile Fetch Failed: " . $userResponse->body(),
                ]);
            }

            $userData = $userResponse->json();

            // 3. Demo Fetch SIJUNA Data via Gateway Proxy API (GET /api/v1/sijuna/students)
            $studentsResponse = Http::withToken($accessToken)
                ->get(route('api.v1.sijuna.students'));

            $sijunaStudentsData = $studentsResponse->successful() ? $studentsResponse->json() : null;

            // 4. Establish External App Local Session
            $newSession = [
                'user' => $userData,
                'sijuna_students' => $sijunaStudentsData,
                'tokens' => $tokenData,
                'logged_in_at' => now()->toDateTimeString(),
            ];
            session()->put("demo_session_{$appSlug}", $newSession);

            $primaryRole = is_array($userData['roles'] ?? null) ? implode(', ', $userData['roles']) : ($userData['user_type'] ?? 'user');

            return redirect()->route('demo.index', ['appSlug' => $appSlug])
                ->with('success', "Single Sign-On Berhasil! Terhubung sebagai {$userData['name']} ({$primaryRole}) - Data SIJUNA terhubung via Gateway Proxy.");


        } catch (Exception $e) {
            return view('demo.external_app', [
                'appSlug' => $appSlug,
                'app' => $app,
                'localSession' => $localSession,
                'error' => "Gateway SSO Error: " . $e->getMessage(),
            ]);
        }
    }

    /**
     * External App Logout (clears local session)
     */
    public function logout(Request $request, string $appSlug = 'pkl'): RedirectResponse
    {
        session()->forget("demo_session_{$appSlug}");
        return redirect()->route('demo.index', ['appSlug' => $appSlug])
            ->with('info', "Session lokal Aplikasi {$appSlug} telah ditutup.");
    }

    /**
     * Mock health check endpoint for monitoring
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'UP',
            'timestamp' => now()->toIso8601String(),
            'service' => 'External App Service Simulator',
        ]);
    }

    protected function getDemoApplication(string $appSlug): Application
    {
        $app = Application::where('slug', "aplikasi-{$appSlug}")
            ->orWhere('slug', $appSlug)
            ->first();

        if (!$app) {
            $app = Application::first() ?? new Application([
                'name' => "Aplikasi " . strtoupper($appSlug) . " Eksternal",
                'client_id' => "app_{$appSlug}_client",
                'base_url' => url("/demo/{$appSlug}"),
                'status' => 'active',
            ]);
        }

        return $app;
    }
}
