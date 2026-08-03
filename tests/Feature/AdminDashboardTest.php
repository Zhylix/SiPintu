<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_and_view_user_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = ApplicationCategory::create([
            'name' => 'Akademik',
            'slug' => 'akademik',
            'description' => 'Aplikasi Akademik',
            'icon' => 'academic-cap',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $app = Application::create([
            'name' => 'Sistem Nilai',
            'slug' => 'sistem-nilai',
            'client_id' => 'client_test_123',
            'client_secret' => bcrypt('secret'),
            'redirect_uri' => 'https://nilai.sekolah.id/callback',
            'base_url' => 'https://nilai.sekolah.id',
            'description' => 'Portal nilai siswa',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Katalog Aplikasi');
        $response->assertSee('Sistem Nilai');
        $response->assertSee('Akademik');
    }

    public function test_admin_can_access_apps_catalog_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.apps'));

        $response->assertStatus(200);
        $response->assertSee('Katalog Aplikasi');
    }
}
