<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleActiveAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_announcement_can_be_active_at_a_time()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Create first active announcement
        $first = Announcement::create([
            'title' => 'Pengumuman Pertama',
            'content' => 'Isi pengumuman pertama',
            'type' => 'info',
            'target_role' => 'all',
            'is_active' => true,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        $this->assertTrue($first->fresh()->is_active);

        // 2. Create second active announcement
        $second = Announcement::create([
            'title' => 'Pengumuman Kedua',
            'content' => 'Isi pengumuman kedua',
            'type' => 'warning',
            'target_role' => 'user',
            'is_active' => true,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        // First announcement must now be deactivated automatically
        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);

        // Active count in DB must be exactly 1
        $this->assertEquals(1, Announcement::where('is_active', true)->count());
    }

    public function test_announcement_target_role_user_scope()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $announcement = Announcement::create([
            'title' => 'Pengumuman Khusus User',
            'content' => 'Hanya untuk pengguna biasa',
            'type' => 'info',
            'target_role' => 'user',
            'is_active' => true,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        // Student role should see this announcement
        $studentActive = Announcement::active()->forRole('student')->get();
        $this->assertTrue($studentActive->contains($announcement));

        // Teacher role should see this announcement
        $teacherActive = Announcement::active()->forRole('teacher')->get();
        $this->assertTrue($teacherActive->contains($announcement));

        // Admin role should NOT see user-only announcement
        $adminActive = Announcement::active()->forRole('admin')->get();
        $this->assertFalse($adminActive->contains($announcement));
    }
}
