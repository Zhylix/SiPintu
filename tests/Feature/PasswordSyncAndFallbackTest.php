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

    public function test_user_payload_includes_full_jurusan_object_user_type_and_dudi_code(): void
    {
        $jurusan = \App\Models\Jurusan::firstOrCreate(
            ['kode_jurusan' => 'PPLG'],
            ['nama_jurusan' => 'Pengembangan Perangkat Lunak dan Gim']
        );

        $user = User::factory()->create([
            'name' => 'Fauzi Test',
            'email' => 'fauzi@smkn1bangsri.sch.id',
            'role' => 'student',
            'phone' => '081234567890',
            'jurusan_id' => $jurusan->id,
            'external_id' => '12345',
        ]);

        $syncService = app(\App\Services\UserDataSyncService::class);
        $payload = $syncService->getUserPayload($user, ['phone', 'password']);

        $this->assertArrayHasKey('user', $payload);
        $u = $payload['user'];

        $this->assertEquals('student', $u['user_type']);
        $this->assertEquals('081234567890', $u['phone']);
        $this->assertArrayHasKey('jurusan', $u);
        $this->assertIsArray($u['jurusan']);
        $this->assertEquals('PPLG', $u['jurusan']['kode_jurusan']);
        $this->assertEquals('Pengembangan Perangkat Lunak dan Gim', $u['jurusan']['nama_jurusan']);
    }

    public function test_verify_credentials_returns_avatar_jurusan_user_type_and_password(): void
    {
        $app = Application::create([
            'name' => 'Downstream App',
            'slug' => 'downstream-app',
            'client_id' => 'app_verify_enrich',
            'client_secret' => 'sec_verify_enrich',
            'redirect_uri' => 'http://localhost:8004/callback',
            'base_url' => 'http://localhost:8004',
            'status' => 'active',
        ]);

        $jurusan = \App\Models\Jurusan::firstOrCreate(
            ['kode_jurusan' => 'TO'],
            ['nama_jurusan' => 'Teknik Otomotif']
        );

        $user = User::factory()->create([
            'name' => 'Budi Siswa',
            'email' => 'budisiswa@smkn1.sch.id',
            'username' => 'budisiswa',
            'external_id' => '2023099',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'phone' => '089988776655',
            'jurusan_id' => $jurusan->id,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-credentials', [
            'client_id' => 'app_verify_enrich',
            'client_secret' => 'sec_verify_enrich',
            'identity' => 'budisiswa',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['valid']);
        $this->assertNotNull($data['password']);
        $this->assertNotNull($data['password_hash']);
        $this->assertEquals('student', $data['user']['user_type']);
        $this->assertEquals('089988776655', $data['user']['phone']);
        $this->assertIsArray($data['user']['jurusan']);
        $this->assertEquals('TO', $data['user']['jurusan']['kode_jurusan']);
    }

    public function test_user_deletion_broadcasts_to_downstream(): void
    {
        Http::fake([
            'http://localhost:8005/api/sipintu/sync-user' => Http::response(['status' => 'success', 'action' => 'deactivated'], 200),
        ]);

        $app = Application::create([
            'name' => 'Delete Test App',
            'slug' => 'delete-test-app',
            'client_id' => 'app_delete_1',
            'client_secret' => 'sec_delete_1',
            'redirect_uri' => 'http://localhost:8005/callback',
            'base_url' => 'http://localhost:8005',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => 'usertodelete@smkn1.sch.id',
            'role' => 'student',
            'status' => 'active',
        ]);

        $syncService = app(\App\Services\UserDataSyncService::class);
        $result = $syncService->broadcastUserDeletion($user);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['synced_apps_count']);
        $this->assertEquals('synced', $result['details'][$app->id]['status']);
    }

    public function test_downstream_sync_user_prioritizes_explicitly_changed_phone_over_local_edits(): void
    {
        config(['services.sipintu.client_secret' => 'downstream_secret_123']);

        $user = User::factory()->create([
            'email' => 'conflict_user@smkn1.sch.id',
            'name' => 'Nama Downstream',
            'phone' => '081111111111',
            'role' => 'student',
            'status' => 'active',
            'sipintu_last_synced_at' => now()->subHours(2),
            'updated_at' => now()->subHour(1), // local edits present!
        ]);

        $payload = [
            'event' => 'user.updated',
            'user' => [
                'email' => 'conflict_user@smkn1.sch.id',
                'name' => 'Nama SiPintu Baru',
                'phone' => '089999999999', // explicitly updated phone in SiPintu
            ],
            'changed_fields' => ['password', 'phone'], // phone is explicitly changed
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'downstream_secret_123');

        $response = $this->call(
            'POST',
            '/api/sipintu/sync-user',
            [],
            [],
            [],
            [
                'HTTP_X-SiPintu-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payloadJson
        );

        $response->assertStatus(200);

        $user->refresh();
        // Phone must be updated because it was in changed_fields!
        $this->assertEquals('089999999999', $user->phone);
        // Name should be preserved locally because it was NOT in changed_fields!
        $this->assertEquals('Nama Downstream', $user->name);
    }
}
