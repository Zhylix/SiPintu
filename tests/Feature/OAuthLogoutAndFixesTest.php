<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OAuthLogoutAndFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Application $clientApp;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'wa_notify' => true,
        ]);
        $this->user->assignRole($this->adminRole);

        $this->clientApp = Application::create([
            'name' => 'Test App',
            'slug' => 'test-app',
            'client_id' => 'client_test_123',
            'client_secret' => 'secret_test_456',
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/callback,http://127.0.0.1:8001/callback',
            'status' => 'active',
        ]);
        $this->clientApp->roles()->attach($this->adminRole);
    }

    public function test_oauth_logout_endpoint_revokes_tokens_successfully(): void
    {
        $tokenStr = Str::random(80);
        $tokenId = (string) Str::uuid();

        $accessToken = OAuthAccessToken::create([
            'id' => $tokenId,
            'user_id' => $this->user->id,
            'application_id' => $this->clientApp->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        $refreshToken = OAuthRefreshToken::create([
            'id' => (string) Str::uuid(),
            'access_token_id' => $tokenId,
            'token' => Str::random(80),
            'expires_at' => now()->addDays(30),
            'revoked' => false,
        ]);

        $response = $this->withToken($tokenStr)->postJson('/oauth/logout');

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'revoked' => true,
        ]);

        $this->assertTrue($accessToken->fresh()->revoked);
        $this->assertTrue($refreshToken->fresh()->revoked);
    }

    public function test_multi_domain_redirect_uri_authorization_succeeds_for_second_uri(): void
    {
        $this->actingAs($this->user);

        // Second URI from comma-separated 'http://localhost:8001/callback,http://127.0.0.1:8001/callback'
        $response = $this->get('/oauth/authorize?client_id=client_test_123&redirect_uri=http://127.0.0.1:8001/callback&response_type=code');

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('http://127.0.0.1:8001/callback?code=', $targetUrl);
    }

    public function test_user_wa_notify_preference_is_fillable_and_persists(): void
    {
        $this->actingAs($this->user);

        $this->user->update(['wa_notify' => false]);
        $this->assertFalse($this->user->fresh()->wa_notify);

        $this->user->update(['wa_notify' => true]);
        $this->assertTrue($this->user->fresh()->wa_notify);
    }

    public function test_announcement_is_active_boolean_handling(): void
    {
        $this->actingAs($this->user);

        // Test creating announcement with is_active unchecked / false
        $response = $this->post(route('admin.announcements.store'), [
            'title' => 'Pengumuman Nonaktif',
            'content' => 'Konten pengumuman...',
            'type' => 'info',
            'target_role' => 'all',
            'channel' => 'web',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $announcement = Announcement::where('title', 'Pengumuman Nonaktif')->first();
        $this->assertNotNull($announcement);
        $this->assertFalse((bool) $announcement->is_active);
    }
}
