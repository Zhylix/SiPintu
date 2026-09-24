<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class OAuthPkceTest extends TestCase
{
    public function test_pkce_authorization_and_token_exchange_flow(): void
    {
        // 1. Create client application
        $app = Application::create([
            'name' => 'Test CBT App',
            'slug' => 'test-cbt-app-'.Str::random(5),
            'client_id' => 'test_client_'.Str::random(10),
            'client_secret' => 'test_secret_12345',
            'redirect_uri' => 'https://cbt.sekolah.test/callback',
            'base_url' => 'https://cbt.sekolah.test',
            'status' => 'active',
        ]);

        // 2. Create and authenticate user
        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $app->roles()->attach($role);

        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // 3. Generate PKCE verifier and S256 challenge
        $codeVerifier = Str::random(64);
        $rawHash = hash('sha256', $codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($rawHash), '+/', '-_'), '=');

        // 4. Hit /oauth/authorize with PKCE params
        $authorizeResponse = $this->get(route('oauth.authorize', [
            'client_id' => $app->client_id,
            'redirect_uri' => 'https://cbt.sekolah.test/callback',
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]));

        $authorizeResponse->assertStatus(302);
        $redirectUrl = $authorizeResponse->headers->get('Location');
        $this->assertNotNull($redirectUrl);

        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $queryParams);
        $this->assertArrayHasKey('code', $queryParams);
        $authCode = $queryParams['code'];

        // 5. Test token exchange fails with invalid code_verifier
        $invalidExchange = $this->postJson(route('oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $app->client_id,
            'client_secret' => 'test_secret_12345',
            'code' => $authCode,
            'code_verifier' => 'wrong_verifier_1234567890',
        ]);

        $invalidExchange->assertStatus(400);
        $invalidExchange->assertJson(['error' => 'invalid_grant']);

        // Since code is not revoked on failed PKCE, re-try with valid verifier:
        // Actually code is single-use after fetch, let's create a fresh authorization for valid exchange
        $freshAuthResponse = $this->get(route('oauth.authorize', [
            'client_id' => $app->client_id,
            'redirect_uri' => 'https://cbt.sekolah.test/callback',
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]));

        parse_str(parse_url($freshAuthResponse->headers->get('Location'), PHP_URL_QUERY), $freshParams);
        $validAuthCode = $freshParams['code'];

        // 6. Test token exchange succeeds with correct code_verifier
        $validExchange = $this->postJson(route('oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $app->client_id,
            'client_secret' => 'test_secret_12345',
            'code' => $validAuthCode,
            'code_verifier' => $codeVerifier,
        ]);

        $validExchange->assertStatus(200);
        $validExchange->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'refresh_token',
            'id_token',
        ]);

        // Clean up test application and user
        $app->delete();
        $user->delete();
    }
}
