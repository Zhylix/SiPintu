<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.store'), [
            'identity' => $user->email,
            'password' => 'password',
            'account_type' => 'guru',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.store'), [
            'identity' => $user->email,
            'password' => 'wrong-password',
            'account_type' => 'guru',
        ]);

        $response->assertSessionHasErrors(['password']);

        $this->assertGuest();
    }

    public function test_admin_can_authenticate_from_any_tab(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.store'), [
            'identity' => $admin->email,
            'password' => 'password',
            'account_type' => 'siswa',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
