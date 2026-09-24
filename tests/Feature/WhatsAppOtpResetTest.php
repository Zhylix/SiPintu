<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WhatsAppOtpResetTest extends TestCase
{
    public function test_requesting_otp_with_missing_phone_fails_gracefully(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'role' => 'student',
        ]);

        $response = $this->post(route('password.whatsapp.otp'), [
            'identity' => $user->email,
        ]);

        $response->assertSessionHasErrors(['identity']);
        $this->assertNull(Cache::get("wa_reset_otp:{$user->id}"));

        $user->delete();
    }

    public function test_otp_verification_and_password_reset_flow(): void
    {
        $user = User::factory()->create([
            'phone' => '081234567890',
            'role' => 'student',
            'password' => Hash::make('old_password123'),
        ]);

        // Mock WhatsAppService to succeed
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('formatPhoneNumber')->andReturn('6281234567890');
            $mock->shouldReceive('sendMessage')->andReturn(['success' => true]);
        });

        // 1. Request OTP
        $response = $this->post(route('password.whatsapp.otp'), [
            'identity' => $user->email,
        ]);

        $response->assertRedirect(route('password.whatsapp.verify_form', ['uid' => $user->id]));

        $cachedOtp = Cache::get("wa_reset_otp:{$user->id}");
        $this->assertNotNull($cachedOtp);
        $otp = $cachedOtp['otp'];

        // 2. Submit wrong OTP
        $wrongOtpResponse = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => '000000',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $wrongOtpResponse->assertSessionHasErrors(['otp']);
        $this->assertTrue(Hash::check('old_password123', $user->fresh()->password));

        // 3. Submit correct OTP
        $correctOtpResponse = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => $otp,
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $correctOtpResponse->assertRedirect(route('login'));
        $correctOtpResponse->assertSessionHas('success');

        // Verify password was changed and OTP cache was cleared
        $this->assertTrue(Hash::check('new_password123', $user->fresh()->password));
        $this->assertNull(Cache::get("wa_reset_otp:{$user->id}"));

        $user->delete();
    }
}
