<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppGatewayJob;
use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppGatewayMultiSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Application $testApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testApp = Application::create([
            'name' => 'SIJUNA Presensi',
            'slug' => 'sijuna',
            'client_id' => 'client_sijuna_test_123',
            'client_secret' => 'secret_sijuna_456',
            'base_url' => 'https://sijuna.smkn1bangsri.sch.id',
            'redirect_uri' => 'https://sijuna.smkn1bangsri.sch.id/callback',
            'status' => 'active',
            'wa_session_id' => 'app_sijuna',
            'wa_auto_fallback' => true,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/whatsapp/send', [
            'phone' => '08123456789',
            'message' => 'Pesan tanpa otentikasi',
        ]);

        $response->assertStatus(401);
    }

    public function test_downstream_can_queue_whatsapp_message(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'phone' => '08123456789',
            'message' => 'Siswa telah melakukan presensi pada 07:15.',
        ], [
            'X-Client-ID' => $this->testApp->client_id,
            'X-Client-Secret' => 'secret_sijuna_456',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'queued',
                'data' => [
                    'recipient' => '628123456789',
                    'session_id' => 'app_sijuna',
                ],
            ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'application_id' => $this->testApp->id,
            'session_id' => 'app_sijuna',
            'phone_number' => '628123456789',
            'type' => 'gateway',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendWhatsAppGatewayJob::class);
    }

    public function test_downstream_can_send_synchronously_via_own_session(): void
    {
        Http::fake([
            'http://127.0.0.1:3789/session/app_sijuna/send-message' => Http::response([
                'status' => 'success',
                'message' => 'Pesan WhatsApp berhasil dikirim.',
                'data' => [
                    'session_id' => 'app_sijuna',
                    'message_id' => 'msg_12345',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'phone' => '08571234567',
            'message' => 'Kode OTP Anda adalah 123456.',
            'sync' => true,
        ], [
            'X-Client-ID' => $this->testApp->client_id,
            'X-Client-Secret' => 'secret_sijuna_456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'recipient' => '628571234567',
                    'session_id' => 'app_sijuna',
                    'fallback_used' => false,
                ],
            ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'application_id' => $this->testApp->id,
            'session_id' => 'app_sijuna',
            'status' => 'sent',
        ]);
    }

    public function test_downstream_falls_back_to_central_when_session_offline(): void
    {
        Http::fake([
            // Dedicated session fails/offline
            'http://127.0.0.1:3789/session/app_sijuna/send-message' => Http::response([
                'status' => 'error',
                'message' => 'Bot offline',
            ], 503),
            // Central fallback session succeeds
            'http://127.0.0.1:3789/send-message' => Http::response([
                'status' => 'success',
                'message' => 'Pesan WhatsApp berhasil dikirim via fallback.',
                'data' => [
                    'session_id' => 'default',
                    'message_id' => 'msg_fallback_99',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/whatsapp/send', [
            'phone' => '08571234567',
            'message' => 'Notifikasi darurat sekolah.',
            'sync' => true,
        ], [
            'X-Client-ID' => $this->testApp->client_id,
            'X-Client-Secret' => 'secret_sijuna_456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'session_id' => 'default',
                    'fallback_used' => true,
                ],
            ]);
    }

    public function test_downstream_can_check_assigned_session_status(): void
    {
        Http::fake([
            'http://127.0.0.1:3789/session/app_sijuna/status' => Http::response([
                'status' => 'success',
                'connection' => 'open',
                'bot_phone' => '6285711223344',
                'bot_enabled' => true,
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/whatsapp/status', [
            'X-Client-ID' => $this->testApp->client_id,
            'X-Client-Secret' => 'secret_sijuna_456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'whatsapp_gateway' => [
                    'session_id' => 'app_sijuna',
                    'assigned_phone' => '6285711223344',
                    'is_connected' => true,
                    'is_enabled' => true,
                    'auto_fallback_to_central' => true,
                ],
            ]);

        $this->assertEquals('connected', $this->testApp->fresh()->wa_status);
        $this->assertEquals('6285711223344', $this->testApp->fresh()->wa_phone_number);
    }

    public function test_admin_can_access_whatsapp_gateway_dashboard(): void
    {
        Http::fake([
            'http://127.0.0.1:3789/session/default/status' => Http::response([
                'status' => 'success',
                'connection' => 'open',
                'bot_phone' => '6281200001111',
                'bot_enabled' => true,
            ], 200),
            'http://127.0.0.1:3789/sessions' => Http::response([
                'status' => 'success',
                'sessions' => [],
            ], 200),
        ]);

        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['slug' => 'admin', 'display_name' => 'Administrator']
        );

        $adminUser = User::create([
            'name' => 'Super Admin',
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $adminUser->roles()->sync([$adminRole->id]);

        $response = $this->actingAs($adminUser)->get('/admin/whatsapp-gateway');

        $response->assertStatus(200);
        $response->assertSee('Multi-Session WhatsApp Gateway');
        $response->assertSee('SIJUNA Presensi');
    }
}
