<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\WhatsAppService;
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
}
