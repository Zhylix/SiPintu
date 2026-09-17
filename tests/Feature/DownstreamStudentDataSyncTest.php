<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DownstreamStudentDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Application $clientApp;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->student = User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@smkn1bangsri.sch.id',
            'username' => 'siti12345',
            'external_id' => '12345678',
            'role' => 'student',
            'phone' => '081234567890',
            'avatar' => 'avatars/siti_profile.webp',
            'classroom' => 'XII PPLG 1',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->student->assignRole($role);

        $this->clientApp = Application::create([
            'name' => 'Aplikasi PKL',
            'slug' => 'aplikasi-pkl',
            'client_id' => 'client_test_pkl',
            'client_secret' => 'secret_test_pkl_123',
            'base_url' => 'http://localhost:8002',
            'redirect_uri' => 'http://localhost:8002/oauth/callback',
            'status' => 'active',
        ]);

        $this->token = 'test_token_'.Str::random(40);
        OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'application_id' => $this->clientApp->id,
            'token' => $this->token,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);
    }

    public function test_api_v1_user_contains_avatar_url_and_phone(): void
    {
        $response = $this->withToken($this->token)->getJson(route('api.v1.user'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals('081234567890', $json['phone']);
        $this->assertNotEmpty($json['avatar_url']);
        $this->assertStringContainsString('avatars/siti_profile.webp', $json['avatar_url']);
        $this->assertEquals($json['avatar_url'], $json['avatar']);
    }

    public function test_api_v1_user_profile_contains_avatar_url_and_phone(): void
    {
        $response = $this->withToken($this->token)->getJson(route('api.v1.user.profile'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals('081234567890', $json['phone']);
        $this->assertNotEmpty($json['avatar_url']);
        $this->assertStringContainsString('avatars/siti_profile.webp', $json['avatar_url']);
        $this->assertEquals($json['avatar_url'], $json['avatar']);
    }

    public function test_oidc_id_token_contains_avatar_and_phone_claims(): void
    {
        $authCode = 'code_'.Str::random(32);
        OAuthAuthCode::create([
            'id' => $authCode,
            'user_id' => $this->student->id,
            'application_id' => $this->clientApp->id,
            'redirect_uri' => $this->clientApp->redirect_uri,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addMinutes(5),
            'revoked' => false,
        ]);

        $response = $this->postJson(route('oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientApp->client_id,
            'client_secret' => 'secret_test_pkl_123',
            'code' => $authCode,
            'redirect_uri' => $this->clientApp->redirect_uri,
        ]);

        $response->assertStatus(200);
        $idToken = $response->json('id_token');
        $this->assertNotEmpty($idToken);

        // Decode JWT payload
        $parts = explode('.', $idToken);
        $this->assertCount(3, $parts);
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

        $this->assertEquals('081234567890', $payload['phone']);
        $this->assertEquals('081234567890', $payload['phone_number']);
        $this->assertNotEmpty($payload['avatar_url']);
        $this->assertStringContainsString('avatars/siti_profile.webp', $payload['avatar_url']);
        $this->assertEquals($payload['avatar_url'], $payload['picture']);
    }

    public function test_api_v1_alumni_endpoints_contain_avatar_url_and_phone(): void
    {
        $alumniRole = Role::firstOrCreate(['name' => 'alumni', 'guard_name' => 'web']);
        $alumni = User::factory()->create([
            'name' => 'Rian Ardianto',
            'email' => 'rian@smkn1bangsri.sch.id',
            'username' => '11223344',
            'external_id' => '11223344',
            'role' => 'alumni',
            'phone' => '087711223344',
            'avatar' => 'avatars/rian_alumni.webp',
            'classroom' => 'XII PPLG 2',
            'status' => 'active',
        ]);
        $alumni->assignRole($alumniRole);

        // Test GET /api/v1/alumni
        $listResponse = $this->withToken($this->token)->getJson(route('api.v1.alumni'));
        $listResponse->assertStatus(200);

        $items = $listResponse->json('data');
        $this->assertNotEmpty($items);

        $found = collect($items)->firstWhere('email', 'rian@smkn1bangsri.sch.id');
        $this->assertNotNull($found);
        $this->assertEquals('087711223344', $found['phone']);
        $this->assertNotEmpty($found['avatar_url']);
        $this->assertStringContainsString('avatars/rian_alumni.webp', $found['avatar_url']);

        // Test GET /api/v1/alumni/{identifier}
        $detailResponse = $this->withToken($this->token)->getJson(route('api.v1.alumni_detail', ['identifier' => '11223344']));
        $detailResponse->assertStatus(200);
        $detailData = $detailResponse->json('data');

        $this->assertEquals('087711223344', $detailData['phone']);
        $this->assertNotEmpty($detailData['avatar_url']);
        $this->assertStringContainsString('avatars/rian_alumni.webp', $detailData['avatar_url']);
    }
}
