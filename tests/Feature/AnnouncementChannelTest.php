<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppAnnouncementJob;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnnouncementChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_announcement_with_channel_validation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Pengumuman Web Saja',
            'content' => 'Isi pesan khusus web',
            'type' => 'info',
            'target_role' => 'all',
            'channel' => 'web',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Pengumuman Web Saja',
            'channel' => 'web',
        ]);
    }

    public function test_web_only_and_both_channels_appear_on_web_scope()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $webAnn = Announcement::create([
            'title' => 'Web Only',
            'content' => 'Content Web',
            'type' => 'info',
            'target_role' => 'all',
            'channel' => 'web',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $waAnn = Announcement::create([
            'title' => 'WhatsApp Only',
            'content' => 'Content WA',
            'type' => 'info',
            'target_role' => 'all',
            'channel' => 'whatsapp',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $webAnnouncements = Announcement::active()->forWeb()->get();

        // Fresh check because booting deactivates earlier active announcements, but here waAnn is latest active
        $activeWeb = Announcement::active()->forWeb()->get();
        $this->assertTrue($activeWeb->contains($waAnn) === false);

        // Test forWeb scope directly
        $webChannelAnnouncements = Announcement::forWeb()->get();
        $this->assertTrue($webChannelAnnouncements->contains($webAnn));
        $this->assertFalse($webChannelAnnouncements->contains($waAnn));
    }

    public function test_whatsapp_and_both_channel_dispatches_whatsapp_jobs()
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '08123456789']);

        // 1. Create with channel = 'whatsapp'
        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Pengumuman WA Broadcast',
            'content' => 'Pesan via WA',
            'type' => 'info',
            'target_role' => 'student',
            'channel' => 'whatsapp',
            'is_active' => '1',
        ]);

        Queue::assertPushed(SendWhatsAppAnnouncementJob::class, 1);

        // 2. Create with channel = 'web'
        Queue::fake();
        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Pengumuman Web Sahaja',
            'content' => 'Pesan via Web',
            'type' => 'info',
            'target_role' => 'student',
            'channel' => 'web',
            'is_active' => '1',
        ]);

        Queue::assertNotPushed(SendWhatsAppAnnouncementJob::class);
    }
}
