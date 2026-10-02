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

    public function test_multiple_failed_logins_locks_target_account_without_blocking_entire_ip(): void
    {
        config([
            'auth.security.max_attempts' => 15,
            'auth.security.max_ip_attempts' => 30,
        ]);
        $securityService = app(SecurityService::class);
        $testIp = '198.51.100.99';

        Cache::forget("security:failed_login_count:{$testIp}");
        Cache::forget('security:account_locked:'.md5('target_user|'.$testIp));
        BlockedIp::where('ip_address', $testIp)->delete();

        // 14 failed attempts should not lock yet
        for ($i = 1; $i <= 14; $i++) {
            $securityService->recordFailedLogin($testIp, 'target_user');
            $this->assertFalse($securityService->isAccountLocked('target_user', $testIp));
            $this->assertFalse($securityService->isIpBlocked($testIp));
        }

        // 15th failed attempt should lock THIS account, but NOT block the whole school IP
        $securityService->recordFailedLogin($testIp, 'target_user');
        $this->assertTrue($securityService->isAccountLocked('target_user', $testIp));
        $this->assertFalse($securityService->isIpBlocked($testIp));

        // Other accounts from the same IP can still access and are NOT locked
        $this->assertFalse($securityService->isAccountLocked('other_student', $testIp));

        // Clean up
        Cache::forget("security:failed_login_count:{$testIp}");
        Cache::forget('security:account_locked:'.md5('target_user|'.$testIp));
    }

    public function test_cumulative_ip_brute_force_triggers_ip_timeout_without_database_block(): void
    {
        config(['auth.security.max_ip_attempts' => 15]);
        $securityService = app(SecurityService::class);
        $testIp = '198.51.100.99';

        Cache::forget("security:failed_login_count:{$testIp}");
        Cache::forget("security:ip_timeout:{$testIp}");
        BlockedIp::where('ip_address', $testIp)->delete();

        for ($i = 1; $i <= 14; $i++) {
            $securityService->recordFailedLogin($testIp, "user_{$i}");
            $this->assertFalse($securityService->isIpBlocked($testIp));
        }

        // 15th failed attempt from IP triggers 15-minute timeout in cache (no DB row inserted)
        $securityService->recordFailedLogin($testIp, 'user_15');
        $this->assertTrue($securityService->isIpBlocked($testIp));
        $this->assertDatabaseMissing('blocked_ips', ['ip_address' => $testIp]);

        // Test middleware intercepts request with timed out IP
        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get(route('login'));

        $response->assertStatus(403);

        // Clean up
        Cache::forget("security:failed_login_count:{$testIp}");
        Cache::forget("security:ip_timeout:{$testIp}");
        BlockedIp::where('ip_address', $testIp)->delete();
    }

    public function test_pwa_static_assets_bypass_blocked_ip_middleware(): void
    {
        $testIp = '198.51.100.99';
        BlockedIp::updateOrCreate(
            ['ip_address' => $testIp],
            ['reason' => 'Test IP Block', 'is_active' => true]
        );

        $response = $this->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get('/manifest.json');
        $response->assertStatus(200);

        BlockedIp::where('ip_address', $testIp)->delete();
    }

    public function test_admin_can_access_unblock_route_even_if_on_blocked_ip(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_security'],
            [
                'name' => 'Admin Test',
                'email' => 'admin_test_sec@smkn1bangsri.sch.id',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $testIp = '198.51.100.99';
        $blocked = BlockedIp::updateOrCreate(
            ['ip_address' => $testIp],
            ['reason' => 'Test Block', 'is_active' => true]
        );

        // Admin can call unblock route from that IP
        $response = $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->delete(route('admin.monitoring.blocked-ips.destroy', $blocked->id));

        $response->assertRedirect();
        $this->assertFalse((bool) $blocked->fresh()->is_active);

        $blocked->delete();
        $admin->delete();
    }

    public function test_different_users_failing_login_does_not_prematurely_block_school_ip(): void
    {
        $securityService = app(SecurityService::class);
        $schoolIp = '198.51.100.88';

        Cache::forget("security:failed_login_count:{$schoolIp}");
        BlockedIp::where('ip_address', $schoolIp)->delete();

        // 5 different students in a school lab each fail 1 time with their own NIS
        for ($i = 1; $i <= 5; $i++) {
            $securityService->recordFailedLogin($schoolIp, "student_{$i}");
            $this->assertFalse($securityService->isIpBlocked($schoolIp));
        }

        // Clean up
        Cache::forget("security:failed_login_count:{$schoolIp}");
        BlockedIp::where('ip_address', $schoolIp)->delete();
    }

    public function test_role_mismatch_error_does_not_penalize_ip_counter(): void
    {
        $schoolIp = '198.51.100.77';
        Cache::forget("security:failed_login_count:{$schoolIp}");

        // Create a teacher
        $teacher = User::factory()->create([
            'username' => 'guru_test_'.rand(100, 999),
            'password' => Hash::make('secret123'),
            'role' => 'teacher',
            'status' => 'active',
        ]);

        // Teacher accidentally submits on 'siswa' tab
        $response = $this->withServerVariables(['REMOTE_ADDR' => $schoolIp])
            ->post(route('login.store'), [
                'account_type' => 'siswa',
                'nis' => $teacher->username,
                'password' => 'secret123',
            ]);

        $response->assertSessionHasErrors(['nis']);
        $this->assertEquals(0, (int) Cache::get("security:failed_login_count:{$schoolIp}", 0));

        $teacher->delete();
    }

    public function test_authenticated_user_can_access_sso_even_if_ip_is_blocked(): void
    {
        $student = User::factory()->create([
            'username' => 'student_sso_test_'.rand(100, 999),
            'password' => Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $testIp = '198.51.100.99';
        BlockedIp::updateOrCreate(
            ['ip_address' => $testIp],
            ['reason' => 'Test IP Block', 'is_active' => true]
        );

        // Authenticated student accessing /oauth/authorize should not be blocked by CheckBlockedIp with 403
        $response = $this->actingAs($student)
            ->withServerVariables(['REMOTE_ADDR' => $testIp])
            ->get('/oauth/authorize');

        // Without query params, OAuthController returns 400 (Client Aplikasi Tidak Valid), NOT 403 (ip_blocked)
        $response->assertStatus(400);

        BlockedIp::where('ip_address', $testIp)->delete();
        $student->delete();
    }

    public function test_artisan_security_unblock_command_unblocks_ip(): void
    {
        $testIp = '198.51.100.99';
        $blocked = BlockedIp::updateOrCreate(
            ['ip_address' => $testIp],
            ['reason' => 'Test IP Block', 'is_active' => true]
        );

        $this->artisan('security:unblock', ['ip' => $testIp])
            ->expectsOutputToContain("Blokir pada IP address [{$testIp}] berhasil dibuka")
            ->assertSuccessful();

        $this->assertFalse((bool) $blocked->fresh()->is_active);

        $blocked->delete();
    }
}
