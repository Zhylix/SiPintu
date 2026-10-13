<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password_successfully_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'must_change_password' => true,
            'phone' => '081234567890',
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'oldpassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456', $user->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_duplicate_submission_does_not_fail_with_invalid_current_password_error(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'phone' => '081234567890',
        ]);

        // First submit: changes password to NewPassword456
        $res1 = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'oldpassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);
        $res1->assertSessionHas('success');

        // Second submit (rapid double click / replay with the same payload):
        // Must NOT return false error "Kata sandi saat ini tidak cocok", must return success!
        $res2 = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'oldpassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $res2->assertSessionHasNoErrors();
        $res2->assertSessionHas('success');
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'wrongpassword',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertTrue(Hash::check('correctpassword', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_same_as_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SameOldPassword123'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'SameOldPassword123',
                'password' => 'SameOldPassword123',
                'password_confirmation' => 'SameOldPassword123',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_cannot_update_password_with_default_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('initialpass123'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'initialpass123',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_cannot_update_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password'), [
                'current_password' => 'oldpassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'DifferentPassword456',
                'phone' => '081234567890',
                'active_section' => 'ganti_password',
            ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_can_view_email_reset_password_page(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Setel Ulang Kata Sandi');
        $response->assertSee($user->email);
    }

    public function test_user_can_reset_password_via_email_token_and_resets_must_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'must_change_password' => true,
        ]);
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPassword123',
            'password_confirmation' => 'BrandNewPassword123',
        ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123', $user->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_user_can_verify_whatsapp_otp_and_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
            'phone' => '081234567890',
            'must_change_password' => true,
        ]);

        Cache::put("wa_reset_otp:{$user->id}", [
            'otp' => '654321',
            'phone' => '6281234567890',
            'user_id' => $user->id,
        ], 300);

        $response = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => '654321',
            'password' => 'BrandNewPassword123',
            'password_confirmation' => 'BrandNewPassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123', $user->password));
        $this->assertFalse($user->must_change_password);
    }
}
