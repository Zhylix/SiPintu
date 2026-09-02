<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteLogoFaviconTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_default_logo_used_as_favicon_in_layouts()
    {
        $defaultLogo = asset('images/logo-smkn1bangsri.png');

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('<link rel="icon" href="' . $defaultLogo . '"', false);
    }

    public function test_custom_logo_crud_updates_favicon_in_tab()
    {
        Storage::fake('public');

        $customLogo = UploadedFile::fake()->image('custom-school-logo.png', 300, 300);

        $response = $this->actingAs($this->admin)->post(route('admin.settings.logo.update'), [
            'logo' => $customLogo,
        ]);

        $response->assertRedirect();
        
        $logoPath = Setting::get('site_logo');
        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $customLogoUrl = Setting::getLogoUrl();

        $pageResponse = $this->actingAs($this->admin)->get(route('admin.settings.index'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('<link rel="icon" href="' . $customLogoUrl . '"', false);
    }

    public function test_resetting_custom_logo_reverts_favicon_to_default()
    {
        Storage::fake('public');

        $customLogo = UploadedFile::fake()->image('custom-logo.png', 100, 100);
        $path = $customLogo->store('settings', 'public');
        Setting::set('site_logo', $path);

        $this->assertEquals(Setting::getLogoUrl(), Storage::disk('public')->url($path));

        $response = $this->actingAs($this->admin)->delete(route('admin.settings.logo.destroy'));

        $response->assertRedirect();
        $this->assertNull(Setting::get('site_logo'));

        $defaultLogo = asset('images/logo-smkn1bangsri.png');
        $this->assertEquals($defaultLogo, Setting::getLogoUrl());
    }
}
