<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserAndApp(): array
    {
        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'name' => 'Ahmad Tes API',
            'email' => 'ahmad.tes@sijuna.sch.id',
            'username' => '12345678',
            'external_id' => '12345678',
            'role' => 'student',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole($role);

        $app = Application::create([
            'name' => 'Aplikasi Testing API',
            'slug' => 'app-testing-api',
            'client_id' => 'client_api_test_id',
            'client_secret' => 'client_api_test_secret',
            'base_url' => 'http://localhost:9000',
            'redirect_uri' => 'http://localhost:9000/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'test_bearer_token_'.Str::random(40);
        $accessToken = OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        return [$user, $app, $tokenStr, $accessToken];
    }

    public function test_public_ping_and_health_endpoints(): void
    {
        $resPing = $this->getJson('/api/v1/ping');
        $resPing->assertStatus(200)->assertJson(['status' => 'online']);

        $resHealth = $this->getJson('/api/v1/health');
        $resHealth->assertStatus(200)->assertJson(['status' => 'online']);
    }

    public function test_validate_client_credentials_endpoint(): void
    {
        [$user, $app] = $this->setupUserAndApp();

        $res = $this->postJson(route('api.v1.validate_client'), [
            'client_id' => $app->client_id,
            'client_secret' => 'client_api_test_secret',
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'valid' => true,
        ]);
    }

    public function test_gateway_status_endpoint(): void
    {
        [$user, $app, $tokenStr] = $this->setupUserAndApp();

        $res = $this->withToken($tokenStr)->getJson(route('api.v1.gateway.status'));
        $res->assertStatus(200);
        $res->assertJsonStructure(['gateway_name', 'timestamp', 'database', 'cache', 'downstream_clients']);
    }

    public function test_user_identity_and_profile_endpoints(): void
    {
        [$user, $app, $tokenStr] = $this->setupUserAndApp();

        $resUser = $this->withToken($tokenStr)->getJson(route('api.v1.user'));
        $resUser->assertStatus(200);
        $resUser->assertJson(['email' => 'ahmad.tes@sijuna.sch.id', 'password_sync_required' => true]);

        $resProfile = $this->withToken($tokenStr)->getJson(route('api.v1.user.profile'));
        $resProfile->assertStatus(200);
        $resProfile->assertJson(['email' => 'ahmad.tes@sijuna.sch.id']);

        $resRoles = $this->withToken($tokenStr)->getJson(route('api.v1.user.roles'));
        $resRoles->assertStatus(200);
        $resRoles->assertJsonStructure(['user_id', 'roles', 'permissions']);
    }

    public function test_password_sync_endpoint(): void
    {
        [$user, $app, $tokenStr] = $this->setupUserAndApp();

        $resGet = $this->withToken($tokenStr)->getJson(route('api.v1.user.password_sync'));
        $resGet->assertStatus(200)->assertJson(['status' => 'success']);

        $resPost = $this->withToken($tokenStr)->postJson(route('api.v1.user.password_sync'), [
            'email' => 'ahmad.tes@sijuna.sch.id',
        ]);
        $resPost->assertStatus(200)->assertJson(['status' => 'success', 'email' => 'ahmad.tes@sijuna.sch.id']);
    }

    public function test_sijuna_proxy_endpoints(): void
    {
        [$user, $app, $tokenStr] = $this->setupUserAndApp();

        $resStudents = $this->withToken($tokenStr)->getJson(route('api.v1.sijuna.students'));
        $resStudents->assertStatus(200);

        $resTeachers = $this->withToken($tokenStr)->getJson(route('api.v1.sijuna.teachers'));
        $resTeachers->assertStatus(200);
    }

    public function test_openid_configuration_and_jwks(): void
    {
        $resConfig = $this->getJson('/oauth/openid-configuration');
        $resConfig->assertStatus(200)->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint', 'userinfo_endpoint']);

        $resJwks = $this->getJson('/oauth/jwks.json');
        $resJwks->assertStatus(200)->assertJsonStructure(['keys']);
    }

    public function test_oauth_token_exchange_and_refresh(): void
    {
        [$user, $app, $tokenStr, $accessToken] = $this->setupUserAndApp();

        $code = Str::random(40);
        OAuthAuthCode::create([
            'id' => $code,
            'user_id' => $user->id,
            'application_id' => $app->id,
            'redirect_uri' => 'http://localhost:9000/callback',
            'scopes' => 'openid profile email',
            'expires_at' => now()->addMinutes(10),
            'revoked' => false,
        ]);

        // Authorization Code Grant
        $resCodeExchange = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $app->client_id,
            'client_secret' => 'client_api_test_secret',
            'redirect_uri' => 'http://localhost:9000/callback',
            'code' => $code,
        ]);

        $resCodeExchange->assertStatus(200);
        $resCodeExchange->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'refresh_token', 'id_token', 'password']);

        $refreshTokenStr = $resCodeExchange->json('refresh_token');

        // Refresh Token Grant
        $resRefresh = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $app->client_id,
            'client_secret' => 'client_api_test_secret',
            'refresh_token' => $refreshTokenStr,
        ]);

        $resRefresh->assertStatus(200);
        $resRefresh->assertJsonStructure(['access_token', 'refresh_token', 'id_token', 'password']);
    }
}
