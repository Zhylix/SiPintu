<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApplicationLogoTest extends TestCase
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

    public function test_admin_can_create_application_with_logo_upload()
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('app-logo.png', 200, 200);

        $response = $this->actingAs($this->admin)->post(route('admin.applications.store'), [
            'name' => 'Portal Perpustakaan',
            'slug' => 'portal-perpustakaan',
            'base_url' => 'https://perpus.smk.sch.id',
            'client_id' => 'app_perpus123',
            'client_secret' => 'sec_secret1234567890123456789012',
            'redirect_uri' => 'https://perpus.smk.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'roles' => [$this->adminRole->id],
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('admin.applications.index'));
        $this->assertDatabaseHas('applications', [
            'slug' => 'portal-perpustakaan',
        ]);

        $app = Application::where('slug', 'portal-perpustakaan')->first();
        $this->assertNotNull($app->logo);
        Storage::disk('public')->assertExists($app->logo);
        $this->assertNotNull($app->logo_url);
    }

    public function test_admin_can_update_application_logo()
    {
        Storage::fake('public');

        $initialLogo = UploadedFile::fake()->image('initial.png');
        $initialPath = $initialLogo->store('logos', 'public');

        $app = Application::create([
            'name' => 'E-Learning',
            'slug' => 'elearning',
            'base_url' => 'https://elearning.smk.sch.id',
            'client_id' => 'app_elearning',
            'client_secret' => 'secret_12345',
            'redirect_uri' => 'https://elearning.smk.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'logo' => $initialPath,
        ]);
        $app->roles()->sync([$this->adminRole->id]);

        $newLogo = UploadedFile::fake()->image('updated-logo.jpg');

        $response = $this->actingAs($this->admin)->put(route('admin.applications.update', $app), [
            'name' => 'E-Learning SMKN 1',
            'slug' => 'elearning',
            'base_url' => 'https://elearning.smk.sch.id',
            'redirect_uri' => 'https://elearning.smk.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'roles' => [$this->adminRole->id],
            'logo' => $newLogo,
        ]);

        $response->assertRedirect(route('admin.applications.index'));

        $app->refresh();
        Storage::disk('public')->assertMissing($initialPath);
        Storage::disk('public')->assertExists($app->logo);
    }

    public function test_admin_can_remove_application_logo()
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png');
        $logoPath = $logo->store('logos', 'public');

        $app = Application::create([
            'name' => 'E-Kantin',
            'slug' => 'ekantin',
            'base_url' => 'https://ekantin.smk.sch.id',
            'client_id' => 'app_ekantin',
            'client_secret' => 'secret_12345',
            'redirect_uri' => 'https://ekantin.smk.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'logo' => $logoPath,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.applications.destroy-logo', $app));

        $response->assertRedirect();
        $app->refresh();

        $this->assertNull($app->logo);
        $this->assertNull($app->logo_url);
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_deleting_application_cleans_up_logo_file()
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo-to-delete.png');
        $logoPath = $logo->store('logos', 'public');

        $app = Application::create([
            'name' => 'App To Delete',
            'slug' => 'app-to-delete',
            'base_url' => 'https://delete.smk.sch.id',
            'client_id' => 'app_delete',
            'client_secret' => 'secret_12345',
            'redirect_uri' => 'https://delete.smk.sch.id/callback',
            'scopes' => 'openid profile email',
            'status' => 'active',
            'logo' => $logoPath,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.applications.destroy', $app));

        $response->assertRedirect(route('admin.applications.index'));
        $this->assertDatabaseMissing('applications', ['id' => $app->id]);
        Storage::disk('public')->assertMissing($logoPath);
    }
}
