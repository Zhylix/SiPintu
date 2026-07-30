<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationCategoryAndFavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    }

    public function test_admin_can_create_and_manage_application_category(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create Category
        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Akademik & Nilai',
            'description' => 'Kategori aplikasi akademik sekolah',
            'display_order' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('application_categories', [
            'name' => 'Akademik & Nilai',
            'slug' => 'akademik-nilai',
        ]);

        $category = ApplicationCategory::where('slug', 'akademik-nilai')->first();

        // Update Category
        $updateResponse = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Akademik Baru',
            'description' => 'Deskripsi baru',
            'display_order' => 2,
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('application_categories', [
            'id' => $category->id,
            'name' => 'Akademik Baru',
        ]);
    }

    public function test_user_can_toggle_favorite_application(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        $category = ApplicationCategory::create([
            'name' => 'Umum',
            'slug' => 'umum',
        ]);

        $app = Application::create([
            'name' => 'Portal E-Library',
            'slug' => 'e-library',
            'category_id' => $category->id,
            'base_url' => 'https://library.sekolah.id',
            'client_id' => 'app_library123',
            'client_secret' => 'secret_123',
            'redirect_uri' => 'https://library.sekolah.id/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);

        // Toggle Favorite (Add)
        $response = $this->actingAs($user)->post(route('applications.favorite.toggle', $app));
        $response->assertSessionHas('success');
        $this->assertTrue($user->hasFavorited($app));

        // Toggle Favorite (Remove)
        $response2 = $this->actingAs($user)->post(route('applications.favorite.toggle', $app));
        $response2->assertSessionHas('success');
        $this->assertFalse($user->hasFavorited($app));
    }
}
