<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_phone_number_via_quick_endpoint(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($adminRole);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '08123456789',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.update-phone', $student), [
                'phone' => '085123456789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'phone' => '085123456789',
        ]);
    }

    public function test_admin_can_filter_users_by_phone_status(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole($adminRole);

        User::factory()->create(['name' => 'User With Phone', 'phone' => '08123456789']);
        User::factory()->create(['name' => 'User Without Phone', 'phone' => null]);

        $responseWithPhone = $this->actingAs($admin)
            ->get(route('admin.users.index', ['phone_status' => 'with_phone']));

        $responseWithPhone->assertStatus(200);
        $responseWithPhone->assertSee('User With Phone');
        $responseWithPhone->assertDontSee('User Without Phone');

        $responseWithoutPhone = $this->actingAs($admin)
            ->get(route('admin.users.index', ['phone_status' => 'without_phone']));

        $responseWithoutPhone->assertStatus(200);
        $responseWithoutPhone->assertSee('User Without Phone');
    }
}
