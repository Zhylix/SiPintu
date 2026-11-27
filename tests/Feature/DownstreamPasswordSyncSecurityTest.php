<?php

namespace Tests\Feature;

use App\Jobs\SendDownstreamUserSyncWebhookJob;
use App\Models\Application;
use App\Models\OAuthAuthCode;
use App\Models\User;
use App\Services\UserDataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DownstreamPasswordSyncSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_endpoint_does_not_leak_password_hash_when_allow_password_sync_is_disabled(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        $app = Application::create([
            'name' => 'Downstream App Without Password Sync',
            'slug' => 'app-safe',
            'client_id' => 'client_safe_123',
            'client_secret' => 'secret_safe_123',
            'base_url' => 'http://external-app.test',
            'redirect_uri' => 'http://external-app.test/callback',
            'status' => 'active',
            'scopes' => 'openid profile email',
            'allow_password_sync' => false,
        ]);

        $code = 'auth_code_test_123';
        OAuthAuthCode::create([
            'id' => $code,
            'user_id' => $user->id,
            'application_id' => $app->id,
            'redirect_uri' => 'http://external-app.test/callback',
            'scopes' => 'openid profile email',
            'expires_at' => now()->addMinutes(5),
            'revoked' => false,
        ]);

        $response = $this->postJson(route('oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'code' => $code,
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('id_token', $data);
        $this->assertArrayNotHasKey('password', $data, 'Password hash tidak boleh dikirimkan ke downstream app biasa.');
        $this->assertArrayNotHasKey('password_hash', $data, 'Password hash tidak boleh dikirimkan ke downstream app biasa.');
    }

    public function test_token_endpoint_provides_password_hash_when_allow_password_sync_is_explicitly_enabled(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'password' => bcrypt('secret123'),
        ]);

        $app = Application::create([
            'name' => 'Trusted Hybrid Downstream App',
            'slug' => 'app-trusted',
            'client_id' => 'client_trusted_123',
            'client_secret' => 'secret_trusted_123',
            'base_url' => 'http://trusted-app.test',
            'redirect_uri' => 'http://trusted-app.test/callback',
            'status' => 'active',
            'scopes' => 'openid profile email',
            'allow_password_sync' => true,
        ]);

        $code = 'auth_code_trusted_123';
        OAuthAuthCode::create([
            'id' => $code,
            'user_id' => $user->id,
            'application_id' => $app->id,
            'redirect_uri' => 'http://trusted-app.test/callback',
            'scopes' => 'openid profile email',
            'expires_at' => now()->addMinutes(5),
            'revoked' => false,
        ]);

        $response = $this->postJson(route('oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $app->client_id,
            'client_secret' => $app->client_secret,
            'code' => $code,
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('password_hash', $data, 'Aplikasi terpercaya dengan allow_password_sync harus menerima hash password.');
        $this->assertTrue($data['password_sync_required']);
    }

    public function test_user_data_sync_payload_omits_password_for_apps_without_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $safeApp = new Application(['allow_password_sync' => false]);
        $trustedApp = new Application(['allow_password_sync' => true]);

        $syncService = app(UserDataSyncService::class);

        $safePayload = $syncService->getUserPayload($user, ['name'], [], $safeApp);
        $this->assertArrayNotHasKey('password', $safePayload['user']);
        $this->assertArrayNotHasKey('password_hash', $safePayload['user']);
        $this->assertFalse($safePayload['user']['password_sync_required']);

        $trustedPayload = $syncService->getUserPayload($user, ['name'], [], $trustedApp);
        $this->assertArrayHasKey('password_hash', $trustedPayload['user']);
        $this->assertTrue($trustedPayload['user']['password_sync_required']);
    }

    public function test_user_observer_dispatches_sync_job_when_user_created(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'student',
            'name' => 'Budi Santoso',
            'email' => 'budi@test.com',
        ]);

        Queue::assertPushed(SendDownstreamUserSyncWebhookJob::class);
    }
}
