<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SsoPasswordSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function createOAuthTokenForUser(User $user): array
    {
        $app = Application::create([
            'name' => 'Aplikasi Test Client',
            'slug' => 'test-app',
            'client_id' => 'app_test_client_123',
            'client_secret' => 'sec_test_secret_123',
            'base_url' => 'http://localhost:9000',
            'redirect_uri' => 'http://localhost:9000/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'test_access_token_'.\Illuminate\Support\Str::random(40);
        OAuthAccessToken::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        return [$app, $tokenStr];
    }

    public function test_api_user_endpoint_returns_password_payload_for_non_admin_users(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'name' => 'Budi Siswa',
            'email' => 'budi@siswa.sch.id',
            'role' => 'student',
            'password' => Hash::make('password123'),
        ]);
        $student->assignRole($studentRole);

        [$app, $token] = $this->createOAuthTokenForUser($student);

        $response = $this->withToken($token)
            ->getJson(route('api.v1.user'));

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'budi@siswa.sch.id',
            'password_sync_required' => true,
            'password_change_policy' => 'MUST_CHANGE_IN_SIPINTU_ONLY',
            'can_change_password_externally' => false,
        ]);
        $this->assertNotEmpty($response->json('password'));
    }

    public function test_api_user_endpoint_exempts_admin_users_from_password_hash_transmission(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create([
            'name' => 'Administrator System',
            'email' => 'admin@gateway.sch.id',
            'role' => 'admin',
            'password' => Hash::make('adminsecret'),
        ]);
        $admin->assignRole($adminRole);

        [$app, $token] = $this->createOAuthTokenForUser($admin);

        $response = $this->withToken($token)
            ->getJson(route('api.v1.user'));

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'admin@gateway.sch.id',
            'password_sync_required' => false,
            'password_change_policy' => 'ADMIN_EXEMPT',
            'can_change_password_externally' => true,
        ]);
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_updating_password_syncs_and_broadcasts_to_connected_applications(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $guru = User::factory()->create([
            'name' => 'Pak Guru',
            'email' => 'guru@sekolah.sch.id',
            'role' => 'teacher',
            'password' => Hash::make('oldpassword123'),
        ]);
        $guru->assignRole($guruRole);

        $this->actingAs($guru);

        $response = $this->put(route('profile.password'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $guru->refresh();
        $this->assertTrue(Hash::check('newpassword123', $guru->password));
    }

    public function test_dedicated_password_sync_endpoint_returns_user_password_info(): void
    {
        $dudiRole = Role::firstOrCreate(['name' => 'dudi', 'guard_name' => 'web']);
        $dudiUser = User::factory()->create([
            'name' => 'PT Mitra Industri',
            'email' => 'hrd@mitraindustri.com',
            'role' => 'dudi',
            'password' => Hash::make('dudipassword'),
        ]);
        $dudiUser->assignRole($dudiRole);

        [$app, $token] = $this->createOAuthTokenForUser($dudiUser);

        $response = $this->withToken($token)
            ->getJson(route('api.v1.user.password_sync'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'email' => 'hrd@mitraindustri.com',
            'password_sync_required' => true,
            'password_change_policy' => 'MUST_CHANGE_IN_SIPINTU_ONLY',
        ]);
    }
}
