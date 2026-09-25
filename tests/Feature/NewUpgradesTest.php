<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BlockedIp;
use App\Models\OAuthAccessToken;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NewUpgradesTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $admin = User::factory()->create([
                'name' => 'Administrator Test',
                'email' => 'admintest@smkn1bangsri.sch.id',
                'username' => 'admin_test',
                'password' => Hash::make('AdminSecret123!'),
                'role' => 'admin',
                'status' => 'active',
            ]);
        }

        return $admin;
    }

    public function test_admin_can_download_user_import_csv_template(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.users.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('name,email,username,external_id,role', $response->getContent());
    }

    public function test_admin_can_import_users_via_csv(): void
    {
        $admin = $this->getAdminUser();

        $csvData = "name,email,username,external_id,role,jurusan,classroom,phone,password,force_change_password\n"
            ."Siswa Import Test,siswa.import.test@smkn1bangsri.sch.id,siswaimport1,999111,student,PPLG,XII PPLG 1,081233344455,password123,ya\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
            'update_existing' => 1,
            'force_change_password' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $importedUser = User::where('email', 'siswa.import.test@smkn1bangsri.sch.id')->first();
        $this->assertNotNull($importedUser);
        $this->assertEquals('Siswa Import Test', $importedUser->name);
        $this->assertEquals('student', $importedUser->role);
        $this->assertTrue($importedUser->must_change_password);

        // Clean up
        $importedUser->delete();
    }

    public function test_user_with_must_change_password_is_redirected_to_change_password(): void
    {
        $user = User::factory()->create([
            'username' => 'user_force_pw_'.rand(1000, 9999),
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'account_type' => 'siswa',
            'nis' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('active_section', 'ganti_password');

        $user->delete();
    }

    public function test_single_sign_out_revokes_oauth_tokens_on_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'sso_logout_user_'.rand(1000, 9999),
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $app = Application::firstOrCreate(
            ['client_id' => 'test_app_client'],
            [
                'name' => 'Test App',
                'slug' => 'test-app',
                'client_secret' => 'secret123',
                'base_url' => 'https://example.com',
                'redirect_uri' => 'https://example.com/callback',
                'status' => 'active',
            ]
        );

        $token = OAuthAccessToken::create([
            'id' => 'test_token_'.rand(1000, 9999),
            'user_id' => $user->id,
            'application_id' => $app->id,
            'token' => 'plain_token_string_'.rand(1000, 9999),
            'scopes' => 'openid profile',
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user);
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $token->refresh();
        $this->assertTrue((bool) $token->revoked);

        $token->delete();
        $user->delete();
    }

    public function test_admin_can_block_and_unblock_ip_manually(): void
    {
        $admin = $this->getAdminUser();
        $testIp = '203.0.113.88';

        BlockedIp::where('ip_address', $testIp)->delete();

        // 1. Block manually
        $response = $this->actingAs($admin)->post(route('admin.monitoring.blocked-ips.store'), [
            'ip_address' => $testIp,
            'reason' => 'Unit test blocking',
            'duration_hours' => 24,
        ]);

        $response->assertRedirect();
        $blockedRecord = BlockedIp::where('ip_address', $testIp)->first();
        $this->assertNotNull($blockedRecord);
        $this->assertTrue((bool) $blockedRecord->is_active);

        // 2. Unblock
        $responseUnblock = $this->actingAs($admin)->delete(route('admin.monitoring.blocked-ips.destroy', $blockedRecord->id));
        $responseUnblock->assertRedirect();

        $blockedRecord->refresh();
        $this->assertFalse((bool) $blockedRecord->is_active);

        $blockedRecord->delete();
    }

    public function test_security_headers_are_present_in_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_custom_404_error_page_renders_cleanly(): void
    {
        $response = $this->get('/non-existent-page-url-404-test');

        $response->assertStatus(404);
        $this->assertStringContainsString('404 - Halaman Tidak Ditemukan', $response->getContent());
        $this->assertStringContainsString('Kembali ke Beranda', $response->getContent());
    }

    public function test_deploy_check_artisan_command_executes_successfully(): void
    {
        $exitCode = $this->artisan('sipintu:deploy-check', ['--fix' => true])->run();

        $this->assertEquals(0, $exitCode);
    }
}
