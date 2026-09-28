<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    public function test_admin_can_login_via_admin_tab(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator SiPintu',
                'email' => 'admin@smkn1bangsri.sch.id',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->syncRoles([$role]);

        $response = $this->post(route('login.store'), [
            'account_type' => 'admin',
            'identity' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_login_even_if_siswa_tab_is_selected(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator SiPintu',
                'email' => 'admin@smkn1bangsri.sch.id',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->syncRoles([$role]);

        $response = $this->post(route('login.store'), [
            'account_type' => 'siswa',
            'nis' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_auto_provisions_if_missing_from_database(): void
    {
        // Delete all admin users to simulate fresh unseeded database
        User::where('role', 'admin')->orWhere('username', 'admin')->delete();

        $response = $this->post(route('login.store'), [
            'account_type' => 'admin',
            'identity' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'role' => 'admin',
        ]);
    }
}
