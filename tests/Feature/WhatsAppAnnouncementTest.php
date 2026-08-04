<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppAnnouncementJob;
use App\Models\Announcement;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_via_http_fake(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/send-message' => Http::response([
                'status' => 'success',
                'message' => 'Pesan WhatsApp berhasil dikirim.',
            ], 200),
        ]);

        $service = new WhatsAppService();
        $result = $service->sendMessage('08123456789', 'Test pesan');

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return $request->url() == 'http://127.0.0.1:3000/send-message' &&
                   $request['phone'] == '628123456789' &&
                   $request['message'] == 'Test pesan';
        });
    }

    public function test_dispatch_announcement_to_users_creates_logs_and_dispatches_jobs(): void
    {
        Queue::fake();

        $userWithPhone = User::factory()->create([
            'role' => 'student',
            'phone' => '08123456789',
            'status' => 'active',
        ]);

        $userWithoutPhone = User::factory()->create([
            'role' => 'student',
            'phone' => null,
            'status' => 'active',
        ]);

        $announcement = Announcement::create([
            'title' => 'Pengumuman Ujian',
            'content' => 'Ujian dimulai minggu depan.',
            'type' => 'info',
            'target_role' => 'student',
            'is_active' => true,
            'created_by' => $userWithPhone->id,
        ]);

        $service = new WhatsAppService();
        $result = $service->dispatchAnnouncementToUsers($announcement);

        $this->assertEquals(1, $result['dispatched']);
        $this->assertEquals(1, $result['skipped']);

        // Check logged entries in whatsapp_logs table
        $this->assertDatabaseHas('whatsapp_logs', [
            'announcement_id' => $announcement->id,
            'user_id' => $userWithPhone->id,
            'phone_number' => '628123456789',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'announcement_id' => $announcement->id,
            'user_id' => $userWithoutPhone->id,
            'status' => 'failed',
            'error_message' => 'Nomor telepon pengguna kosong atau tidak valid.',
        ]);

        Queue::assertPushed(SendWhatsAppAnnouncementJob::class, 1);
    }

    public function test_send_whatsapp_announcement_job_executes_and_updates_log_status(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/send-message' => Http::response([
                'status' => 'success',
                'message' => 'Pesan terkirim',
            ], 200),
        ]);

        $user = User::factory()->create(['phone' => '08123456789']);
        $announcement = Announcement::create([
            'title' => 'Tes Job',
            'content' => 'Tes isi job',
            'type' => 'info',
            'target_role' => 'all',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $log = WhatsAppLog::create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'phone_number' => '628123456789',
            'message' => 'Tes pesan',
            'status' => 'pending',
        ]);

        $job = new SendWhatsAppAnnouncementJob($log->id);
        $job->handle(new WhatsAppService());

        $this->assertDatabaseHas('whatsapp_logs', [
            'id' => $log->id,
            'status' => 'sent',
            'error_message' => null,
        ]);
    }

    public function test_toggle_bot_power_via_service(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/toggle-power' => Http::response([
                'status' => 'success',
                'bot_enabled' => false,
                'message' => 'Bot WhatsApp berhasil dinonaktifkan (OFF).',
            ], 200),
        ]);

        $service = new WhatsAppService();
        $result = $service->toggleBotPower(false);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['bot_enabled']);
    }

    public function test_admin_can_toggle_bot_power_route(): void
    {
        Http::fake([
            'http://127.0.0.1:3000/toggle-power' => Http::response([
                'status' => 'success',
                'bot_enabled' => false,
                'message' => 'Bot WhatsApp berhasil dinonaktifkan (OFF).',
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.announcements.toggle-bot-power'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
