<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (method_exists($this->admin, 'assignRole')) {
            $this->admin->assignRole($this->adminRole);
        }
    }

    public function test_admin_can_view_application_registry_index_with_stats()
    {
        $category = ApplicationCategory::create([
            'name' => 'Akademik',
            'slug' => 'akademik',
            'display_order' => 1,
        ]);

        $app = Application::create([
            'name' => 'Sistem Informasi Akademik',
            'slug' => 'siakad',
            'category_id' => $category->id,
            'base_url' => 'https://siakad.smkn1bangsri.sch.id',
            'client_id' => 'app_siakad_01',
            'client_secret' => 'hashed_secret',
            'redirect_uri' => 'https://siakad.smkn1bangsri.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'last_health_status' => 'online',
        ]);
        $app->roles()->sync([$this->adminRole->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.applications.index'));

        $response->assertOk();
        $response->assertViewHas('applications');
        $response->assertViewHas('categories');
        $response->assertViewHas('stats');
        $response->assertSee('Sistem Informasi Akademik');
        $response->assertSee('app_siakad_01');
        $response->assertSee('Akademik');
        $response->assertSee('Registry Aplikasi Eksternal');
    }

    public function test_admin_can_search_by_client_id_and_url()
    {
        $app1 = Application::create([
            'name' => 'E-Learning',
            'slug' => 'elearning',
            'base_url' => 'https://elearning.smk.sch.id',
            'client_id' => 'client_elearning_key',
            'client_secret' => 'hashed_secret',
            'redirect_uri' => 'https://elearning.smk.sch.id/callback',
            'scopes' => 'openid',
            'status' => 'active',
        ]);
        $app1->roles()->sync([$this->adminRole->id]);

        $app2 = Application::create([
            'name' => 'Perpustakaan Digital',
            'slug' => 'perpus',
            'base_url' => 'https://perpus.smk.sch.id',
            'client_id' => 'client_perpus_key',
            'client_secret' => 'hashed_secret',
            'redirect_uri' => 'https://perpus.smk.sch.id/callback',
            'scopes' => 'openid',
            'status' => 'active',
        ]);
        $app2->roles()->sync([$this->adminRole->id]);

        // Search by client_id substring
        $response1 = $this->actingAs($this->admin)->get(route('admin.applications.index', ['search' => 'elearning_key']));
        $response1->assertOk();
        $response1->assertSee('E-Learning');
        $response1->assertDontSee('Perpustakaan Digital');

        // Search by base_url substring
        $response2 = $this->actingAs($this->admin)->get(route('admin.applications.index', ['search' => 'perpus.smk']));
        $response2->assertOk();
        $response2->assertSee('Perpustakaan Digital');
        $response2->assertDontSee('E-Learning');
    }
}
