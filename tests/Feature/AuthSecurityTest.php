<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    public function test_user_with_valid_credentials_can_login_and_triggers_onboarding_notice_if_default_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser_'.rand(1000, 9999),
            'password' => Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
            'phone' => null,
        ]);

        $this->assertTrue($user->needsPasswordChange());
        $this->assertTrue($user->needsWhatsAppPhone());
        $this->assertTrue($user->needsSecurityOnboarding());

        $response = $this->post(route('login.store'), [
            'account_type' => 'siswa',
            'nis' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->delete();
    }

    public function test_multiple_failed_login_attempts_triggers_ip_block(): void
    {
        $securityService = app(SecurityService::class);
        $testIp = '198.51.100.99';

        Cache::forget("security:failed_login_count:{$testIp}");
        BlockedIp::where('ip_address', $testIp)->delete();

        // 4 failed attempts should not block yet
        for ($i = 1; $i <= 4; $i++) {
            $securityService->recordFailedLogin($testIp, 'target_user');
            $this->assertFalse($securityService->isIpBlocked($testIp));
        }

        // 5th failed attempt should trigger auto-block
        $securityService->recordFailedLogin($testIp, 'target_user');
        $this->assertTrue($securityService->isIpBlocked($testIp));

        // Test middleware intercepts request with blocked IP
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get(route('login'));

        $response->assertStatus(403);

        // Clean up
        Cache::forget("security:failed_login_count:{$testIp}");
        BlockedIp::where('ip_address', $testIp)->delete();
    }
}
