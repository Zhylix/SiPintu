<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementPopupTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin Test',
                'username' => 'admintest',
                'email' => 'admintest@smkn1bangsri.sch.id',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]);
        }

        return $admin;
    }

    protected function getStudentUser(): User
    {
        $student = User::where('role', 'student')->first();
        if (! $student) {
            $student = User::create([
                'name' => 'Student Test',
                'username' => 'studenttest',
                'email' => 'studenttest@smkn1bangsri.sch.id',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'status' => 'active',
            ]);
        }

        return $student;
    }

    public function test_active_announcement_renders_in_popup_component_for_students(): void
    {
        $admin = $this->getAdminUser();
        $student = $this->getStudentUser();

        // Create an active announcement
        $announcement = Announcement::create([
            'title' => 'Pengumuman Ujian Semester Genap',
            'content' => 'Seluruh siswa diharapkan mempersiapkan kartu peserta ujian.',
            'type' => 'warning',
            'target_role' => 'student',
            'channel' => 'both',
            'is_active' => true,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('sipintuAnnouncementModal');
        $response->assertSee('Pengumuman Ujian Semester Genap');
        $response->assertSee('warning');
        $response->assertSee('Buka Pop Up');
    }

    public function test_announcement_index_has_popup_preview_trigger(): void
    {
        $admin = $this->getAdminUser();

        $announcement = Announcement::create([
            'title' => 'Maintenance Server Gateway Malam Ini',
            'content' => 'Server akan dimatikan sementara pukul 23:00 WIB untuk pemeliharaan rutin.',
            'type' => 'danger',
            'target_role' => 'all',
            'channel' => 'both',
            'is_active' => true,
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.announcements.index'));

        $response->assertStatus(200);
        $response->assertSee('Maintenance Server Gateway Malam Ini');
        $response->assertSee('open-announcement-popup');
        $response->assertSee('Pop Up');
    }

    public function test_announcement_create_form_has_preview_popup_button(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.announcements.create'));

        $response->assertStatus(200);
        $response->assertSee('previewFormAnnouncement');
        $response->assertSee('Pratinjau Pop Up');
    }
}
