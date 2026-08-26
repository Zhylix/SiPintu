<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_creates_and_populates_slug_column()
    {
        $role = Role::create([
            'name' => 'Custom Role Test',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Custom Role Test',
            'slug' => 'custom-role-test',
        ]);

        $this->assertEquals('custom-role-test', $role->slug);
    }

    public function test_can_access_application_queries_role_slug_without_500_error()
    {
        $category = ApplicationCategory::create([
            'name' => 'General',
            'slug' => 'general',
        ]);

        $role = Role::create([
            'name' => 'student',
            'slug' => 'student',
            'guard_name' => 'web',
        ]);

        $app = Application::create([
            'name' => 'Test App',
            'slug' => 'test-app',
            'category_id' => $category->id,
            'base_url' => 'https://testapp.com',
            'redirect_uri' => 'https://testapp.com/callback',
            'client_id' => 'client_test_123',
            'client_secret' => Str::random(32),
            'status' => 'active',
        ]);

        $app->roles()->attach($role);

        $user = User::factory()->create([
            'role' => 'student',
        ]);
        $user->assignRole($role);

        $this->assertTrue($user->canAccessApplication($app));
        $this->assertTrue($app->isRoleAllowed($role));
        $this->assertTrue($app->isRoleAllowed('student'));
    }
}
