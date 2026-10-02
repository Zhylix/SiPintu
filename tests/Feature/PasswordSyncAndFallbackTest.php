<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordSyncAndFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_verify_credentials_endpoint_succeeds_with_new_password_and_returns_hash(): void
    {
        $app = Application::create([
            'name' => 'CBT Smkn1',
            'slug' => 'cbt-smkn1',
            'client_id' => 'app_test_123',
            'client_secret' => 'sec_test_secret_456',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'base_url' => 'http://localhost:8001',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'siswa@smkn1bangsri.sch.id',
            'username' => 'siswa123',
            'external_id' => '2023001',
            'password' => Hash::make('new_password_secret'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-credentials', [
            'client_id' => 'app_test_123',
            'client_secret' => 'sec_test_secret_456',
            'identity' => '2023001',
            'password' => 'new_password_secret',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'status' => 'success',
            'user' => [
                'email' => 'siswa@smkn1bangsri.sch.id',
            ],
            'password_sync_required' => true,
        ]);

        $this->assertNotEmpty($response->json('password_hash'));
        $this->assertTrue(Hash::check('new_password_secret', $response->json('password_hash')));
    }

    public function test_verify_credentials_endpoint_rejects_wrong_password(): void
    {
        $app = Application::create([
            'name' => 'CBT Smkn1',
            'slug' => 'cbt-smkn1',
            'client_id' => 'app_test_123',
            'client_secret' => 'sec_test_secret_456',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'base_url' => 'http://localhost:8001',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'siswa@smkn1bangsri.sch.id',
            'password' => Hash::make('correct_password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-credentials', [
            'client_id' => 'app_test_123',
            'client_secret' => 'sec_test_secret_456',
            'identity' => 'siswa@smkn1bangsri.sch.id',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'valid' => false,
            'status' => 'invalid_credentials',
        ]);
    }

    public function test_verify_credentials_never_exposes_admin_password_hash(): void
    {
        $app = Application::create([
            'name' => 'CBT Admin Check',
            'slug' => 'cbt-admin-check',
            'client_id' => 'app_admin_chk',
            'client_secret' => 'sec_admin_chk',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'base_url' => 'http://localhost:8001',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@smkn1bangsri.sch.id',
            'password' => Hash::make('superadmin_password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-credentials', [
            'client_id' => 'app_admin_chk',
            'client_secret' => 'sec_admin_chk',
            'identity' => 'admin@smkn1bangsri.sch.id',
            'password' => 'superadmin_password',
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('password_hash'));
        $this->assertFalse($response->json('password_sync_required'));
        $this->assertEquals('ADMIN_EXEMPT', $response->json('password_change_policy'));
    }

    public function test_verify_credentials_locks_account_after_consecutive_failures(): void
    {
        $app = Application::create([
            'name' => 'Bruteforce Test App',
            'slug' => 'bf-test-app',
            'client_id' => 'app_bf_test',
            'client_secret' => 'sec_bf_test',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'base_url' => 'http://localhost:8001',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'victim@smkn1bangsri.sch.id',
            'password' => Hash::make('real_password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Attempt 15 wrong passwords to hit MAX_ATTEMPTS (15 attempts within 3 minutes)
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/api/v1/auth/verify-credentials', [
                'client_id' => 'app_bf_test',
                'client_secret' => 'sec_bf_test',
                'identity' => 'victim@smkn1bangsri.sch.id',
                'password' => 'wrong_guess_'.$i,
            ]);
        }

        // 16th attempt should be blocked with 429 Account Locked
        $blockedResponse = $this->postJson('/api/v1/auth/verify-credentials', [
            'client_id' => 'app_bf_test',
            'client_secret' => 'sec_bf_test',
            'identity' => 'victim@smkn1bangsri.sch.id',
            'password' => 'real_password',
        ]);

        $blockedResponse->assertStatus(429);
        $blockedResponse->assertJson([
            'valid' => false,
            'status' => 'account_locked',
        ]);
    }

    public function test_oauth_token_supports_password_grant(): void
    {
        $app = Application::create([
            'name' => 'Portal Siswa',
            'slug' => 'portal-siswa',
            'client_id' => 'app_pwd_grant',
            'client_secret' => 'sec_pwd_grant',
            'redirect_uri' => 'http://localhost:8002/oauth/callback',
            'base_url' => 'http://localhost:8002',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'budi@smkn1bangsri.sch.id',
            'password' => Hash::make('budi_password_baru'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => 'app_pwd_grant',
            'client_secret' => 'sec_pwd_grant',
            'username' => 'budi@smkn1bangsri.sch.id',
            'password' => 'budi_password_baru',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'refresh_token',
            'id_token',
            'password',
            'password_hash',
        ]);

        $this->assertTrue(Hash::check('budi_password_baru', $response->json('password_hash')));
    }

    public function test_webhook_delivery_supports_resilient_multi_tier_fallback(): void
    {
        Http::fake([
            'http://localhost:8003/api/sipintu/sync-user' => Http::response(null, 404),
            'http://localhost:8003/sipintu/sync-user' => Http::response(null, 404),
            'http://localhost:8003/api/sipintu/sync-password' => Http::response(['status' => 'password_synced'], 200),
        ]);

        $app = Application::create([
            'name' => 'Legacy App',
            'slug' => 'legacy-app',
            'client_id' => 'app_legacy_1',
            'client_secret' => 'sec_legacy_1',
            'redirect_uri' => 'http://localhost:8003/callback',
            'base_url' => 'http://localhost:8003',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'testuser@smkn1.sch.id',
            'password' => Hash::make('secret_pwd_999'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $syncService = app(\App\Services\UserDataSyncService::class);
        $result = $syncService->broadcastUserUpdate($user, ['password'], force: true);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('synced', $result['details'][$app->id]['status']);
    }
}
