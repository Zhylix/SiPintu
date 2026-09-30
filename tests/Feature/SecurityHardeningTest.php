<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_sync_user_webhook_rejects_unauthenticated_requests(): void
    {
        $response = $this->postJson('/api/sipintu/sync-user', [
            'email' => 'admin@smkn1bangsri.sch.id',
            'role' => 'admin',
            'password' => 'hacked123',
        ]);

        // Expect 401 or 403 (unauthorized/forbidden)
        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_alumni_endpoint_masks_personal_phone_and_email_for_unauthenticated_requests(): void
    {
        $alumni = User::factory()->create([
            'role' => 'alumni',
            'email' => 'alumnitest99@example.com',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/alumni?search=alumnitest99');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $found = collect($data)->firstWhere('id', (string) $alumni->id);
        $this->assertNotNull($found);

        // Phone and email must be masked for public requests
        $this->assertStringContainsString('****', (string) $found['phone']);
        $this->assertStringContainsString('*', (string) $found['email']);
        $this->assertNotEquals('081234567890', $found['phone']);
        $this->assertNotEquals('alumnitest99@example.com', $found['email']);

        $alumni->delete();
    }

    public function test_otp_verification_invalidates_code_after_three_wrong_attempts(): void
    {
        $user = User::factory()->create([
            'username' => 'otptest_'.rand(1000, 9999),
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'phone' => '081234567890',
        ]);

        $otpKey = "wa_reset_otp:{$user->id}";
        $attemptsKey = "wa_reset_otp_attempts:{$user->id}";

        Cache::put($otpKey, [
            'otp' => '654321',
            'phone' => '081234567890',
            'user_id' => $user->id,
        ], 300);
        Cache::forget($attemptsKey);

        // Attempt 1: Wrong OTP
        $res1 = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => '111111',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $res1->assertSessionHasErrors('otp');
        $this->assertTrue(Cache::has($otpKey));
        $this->assertEquals(1, (int) Cache::get($attemptsKey));

        // Attempt 2: Wrong OTP
        $res2 = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => '222222',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $res2->assertSessionHasErrors('otp');
        $this->assertTrue(Cache::has($otpKey));
        $this->assertEquals(2, (int) Cache::get($attemptsKey));

        // Attempt 3: 3rd Wrong OTP triggers invalidation and redirect to password request
        $res3 = $this->post(route('password.whatsapp.verify'), [
            'user_id' => $user->id,
            'otp' => '333333',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $res3->assertRedirect(route('password.request'));
        $this->assertFalse(Cache::has($otpKey), 'OTP code should be destroyed after 3 failed attempts');

        $user->delete();
    }
}
