<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppBotStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_bot_status_and_trigger_bot_logout(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/status' => Http::response([
                'status' => 'success',
                'connection' => 'open',
                'bot_phone' => '6281233096051',
                'qr_code' => null,
            ], 200),
            'http://127.0.0.1:3000/logout' => Http::response([
                'status' => 'success',
                'message' => 'Sesi bot WhatsApp berhasil di-logout.',
            ], 200),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($adminRole);

        // Test index page shows bot status
        $response = $this->actingAs($admin)->get(route('admin.announcements.index'));
        $response->assertStatus(200);
        $response->assertSee('Status Server Bot WhatsApp Sending');
        $response->assertSee('6281233096051');

        // Test logout action
        $logoutResponse = $this->actingAs($admin)->post(route('admin.announcements.logout-bot'));
        $logoutResponse->assertRedirect();
        $logoutResponse->assertSessionHas('success');
    }

    public function test_admin_views_active_qr_code_when_disconnected(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/status' => Http::response([
                'status' => 'success',
                'connection' => 'close',
                'bot_phone' => null,
                'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'bot_enabled' => true,
            ], 200),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->get(route('admin.announcements.index'));
        $response->assertStatus(200);
        $response->assertSee('Status Server Bot WhatsApp Sending');
        $response->assertSee('Belum Terhubung');
        $response->assertSee('Scan QR Code');

        $statusResponse = $this->actingAs($admin)->get(route('admin.announcements.bot-status'));
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'online' => true,
            'data' => [
                'connection' => 'close',
                'bot_phone' => null,
                'qr_code' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
        ]);
    }
}
