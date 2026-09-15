<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SiPintuSyncUserWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $clientSecret = 'test_webhook_secret_key_12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.sipintu.client_secret', $this->clientSecret);
    }

    /**
     * Helper to send signed POST request
     */
    protected function postSignedJson(string $uri, array $data, ?string $customSignature = null)
    {
        $content = json_encode($data);
        $signature = $customSignature ?? hash_hmac('sha256', $content, $this->clientSecret);

        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_SIPINTU_SIGNATURE' => $signature,
            ],
            $content
        );
    }

    public function test_sync_user_rejects_missing_signature_when_secret_is_configured(): void
    {
        $payload = [
            'user' => [
                'email' => 'test@smkn1bangsri.sch.id',
                'name' => 'Budi Test',
            ],
        ];

        $response = $this->postJson('/api/sipintu/sync-user', $payload);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Missing X-SiPintu-Signature header.',
        ]);
    }

    public function test_sync_user_rejects_invalid_hmac_signature(): void
    {
        $payload = [
            'user' => [
                'email' => 'test@smkn1bangsri.sch.id',
                'name' => 'Budi Test',
            ],
        ];

        $response = $this->postSignedJson('/api/sipintu/sync-user', $payload, 'invalid_signature_hash');

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Invalid signature.',
        ]);
    }

    public function test_sync_user_creates_new_user_if_not_in_local_database(): void
    {
        Log::shouldReceive('info')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'user created')
                && in_array('email', $context['updated_fields'] ?? [])
                && in_array('name', $context['updated_fields'] ?? [])
                && empty($context['skipped_fields'] ?? []);
        });

        $payload = [
            'user' => [
                'external_id' => '100123',
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@smkn1bangsri.sch.id',
                'username' => 'siti1001',
                'role' => 'student',
                'classroom' => 'XI PPLG 2',
                'phone' => '08123456789',
                'status' => 'active',
                'avatar_url' => 'https://sipintu.smkn1bangsri.sch.id/avatars/siti.webp',
                'password' => Hash::make('password123'),
            ],
        ];

        $response = $this->postSignedJson('/api/sipintu/sync-user', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'action' => 'created',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'siti@smkn1bangsri.sch.id',
            'name' => 'Siti Nurhaliza',
            'classroom' => 'XI PPLG 2',
            'phone' => '08123456789',
            'role' => 'student',
            'status' => 'active',
        ]);

        $createdUser = User::where('email', 'siti@smkn1bangsri.sch.id')->first();
        $this->assertNotNull($createdUser->sipintu_last_synced_at);
        $this->assertTrue($createdUser->updated_at->equalTo($createdUser->sipintu_last_synced_at) || $createdUser->updated_at->lte($createdUser->sipintu_last_synced_at));
    }

    public function test_sync_user_overwrites_all_fields_when_never_synced_before(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'user@smkn1bangsri.sch.id',
            'phone' => '0811111111',
            'classroom' => 'X RPL 1',
            'role' => 'student',
            'status' => 'active',
            'sipintu_last_synced_at' => null,
        ]);

        $payload = [
            'user' => [
                'email' => 'user@smkn1bangsri.sch.id',
                'name' => 'Nama Baru Dari SiPintu',
                'phone' => '0822222222',
                'classroom' => 'XI RPL 1',
                'role' => 'student',
                'status' => 'active',
            ],
        ];

        $response = $this->postSignedJson('/api/sipintu/sync-user', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'action' => 'updated',
        ]);

        $user->refresh();
        $this->assertEquals('Nama Baru Dari SiPintu', $user->name);
        $this->assertEquals('0822222222', $user->phone);
        $this->assertEquals('XI RPL 1', $user->classroom);
        $this->assertNotNull($user->sipintu_last_synced_at);
    }

    public function test_sync_user_overwrites_all_fields_when_no_local_edits_since_last_sync(): void
    {
        $pastTime = now()->subHours(3);

        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'user@smkn1bangsri.sch.id',
            'phone' => '0811111111',
            'classroom' => 'X RPL 1',
            'role' => 'student',
            'status' => 'active',
            'updated_at' => $pastTime,
            'sipintu_last_synced_at' => $pastTime,
        ]);

        $payload = [
            'user' => [
                'email' => 'user@smkn1bangsri.sch.id',
                'name' => 'Nama Baru SiPintu',
                'phone' => '0833333333',
                'classroom' => 'XII RPL 2',
                'role' => 'alumni',
                'status' => 'active',
            ],
        ];

        $response = $this->postSignedJson('/api/sipintu/sync-user', $payload);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Nama Baru SiPintu', $user->name);
        $this->assertEquals('0833333333', $user->phone);
        $this->assertEquals('XII RPL 2', $user->classroom);
        $this->assertEquals('alumni', $user->role);
    }

    public function test_sync_user_protects_local_edits_and_only_updates_always_synced_fields(): void
    {
        $lastSyncTime = now()->subHours(2);
        $localEditTime = now()->subHour(1);

        $user = User::factory()->create([
            'name' => 'Nama Diedit Lokal Oleh User',
            'email' => 'budi.lama@smkn1bangsri.sch.id',
            'phone' => '0899999999',
            'classroom' => 'XII TKJ 1 (Lokal)',
            'role' => 'student',
            'status' => 'active',
            'avatar' => 'avatars/local_avatar.png',
            'sipintu_last_synced_at' => $lastSyncTime,
            'updated_at' => $localEditTime, // User edited profile locally after last sync!
        ]);

        $newHashedPassword = Hash::make('new_sipintu_secret_pass');

        $payload = [
            'user' => [
                'email' => 'budi.baru@smkn1bangsri.sch.id',
                'name' => 'Nama SiPintu Hendak Menimpa',
                'phone' => '0811111111',
                'classroom' => 'X TKJ 1',
                'role' => 'teacher',
                'status' => 'inactive',
                'avatar_url' => 'https://sipintu.smkn1bangsri.sch.id/avatars/sipintu.webp',
                'password' => $newHashedPassword,
            ],
            'previous' => [
                'email' => 'budi.lama@smkn1bangsri.sch.id',
            ],
        ];

        $response = $this->postSignedJson('/api/sipintu/sync-user', $payload);

        $response->assertStatus(200);
        $json = $response->json();

        // Check skipped fields and updated fields in response
        $this->assertContains('name', $json['skipped_fields']);
        $this->assertContains('phone', $json['skipped_fields']);
        $this->assertContains('classroom', $json['skipped_fields']);
        $this->assertContains('avatar_url', $json['skipped_fields']);

        $this->assertContains('email', $json['updated_fields']);
        $this->assertContains('role', $json['updated_fields']);
        $this->assertContains('status', $json['updated_fields']);
        $this->assertContains('password', $json['updated_fields']);

        $user->refresh();

        // 1. Protected local fields MUST NOT be overwritten
        $this->assertEquals('Nama Diedit Lokal Oleh User', $user->name);
        $this->assertEquals('0899999999', $user->phone);
        $this->assertEquals('XII TKJ 1 (Lokal)', $user->classroom);
        $this->assertEquals('avatars/local_avatar.png', $user->avatar);

        // 2. Always synced fields MUST follow SiPintu
        $this->assertEquals('budi.baru@smkn1bangsri.sch.id', $user->email);
        $this->assertEquals('teacher', $user->role);
        $this->assertEquals('inactive', $user->status);
        $this->assertTrue(Hash::check('new_sipintu_secret_pass', $user->password));

        // 3. sipintu_last_synced_at is updated to now
        $this->assertNotNull($user->sipintu_last_synced_at);

        // 4. updated_at is NOT greater than sipintu_last_synced_at, preventing false local edit on subsequent sync
        $this->assertFalse($user->updated_at->gt($user->sipintu_last_synced_at));
    }

    public function test_subsequent_sync_without_user_edits_allows_normal_sync(): void
    {
        // 1. Start with user having local edit
        $lastSyncTime = now()->subHours(2);
        $localEditTime = now()->subHour(1);

        $user = User::factory()->create([
            'name' => 'Nama Lokal',
            'email' => 'user.local@smkn1bangsri.sch.id',
            'phone' => '0877777777',
            'sipintu_last_synced_at' => $lastSyncTime,
            'updated_at' => $localEditTime,
        ]);

        // 2. First sync occurs, protecting local fields
        $this->postSignedJson('/api/sipintu/sync-user', [
            'user' => [
                'email' => 'user.local@smkn1bangsri.sch.id',
                'name' => 'SiPintu Coba Timpa',
                'role' => 'student',
                'status' => 'active',
            ],
        ])->assertStatus(200);

        $user->refresh();
        $this->assertEquals('Nama Lokal', $user->name);

        // 3. Second sync arrives later, user made NO further local edits
        $this->postSignedJson('/api/sipintu/sync-user', [
            'user' => [
                'email' => 'user.local@smkn1bangsri.sch.id',
                'name' => 'SiPintu Nama Terkini',
                'role' => 'student',
                'status' => 'active',
            ],
        ])->assertStatus(200);

        $user->refresh();
        // Since user did not edit locally after the first sync, it is safely updated
        $this->assertEquals('SiPintu Nama Terkini', $user->name);
    }
}
