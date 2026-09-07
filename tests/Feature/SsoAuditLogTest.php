<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SsoAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_during_sso_flow_creates_sso_audit_log_entry(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'username' => '12345678',
            'external_id' => '12345678',
            'role' => 'student',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $student->assignRole($studentRole);

        // Simulate returning to SSO flow by setting oauth_return_to in session
        $sessionData = ['oauth_return_to' => 'http://localhost:8000/oauth/authorize?client_id=app_test'];

        $response = $this->withSession($sessionData)->post(route('login'), [
            'nis' => '12345678',
            'password' => 'wrongpassword',
            'account_type' => 'siswa',
        ]);

        $response->assertSessionHasErrors(['password']);

        $auditLog = AuditLog::latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('sso_login_failed_password', $auditLog->activity);
        $this->assertTrue($auditLog->isSsoFailure());
        $this->assertTrue($auditLog->metadata['is_sso_failure'] ?? false);
    }

    public function test_sso_access_denied_creates_sso_failure_audit_log(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
        ]);
        $student->assignRole($studentRole);

        // App restricted to teacher role only
        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $app = Application::create([
            'name' => 'App Khusus Guru',
            'slug' => 'app-khusus-guru',
            'client_id' => 'app_guru_only',
            'client_secret' => 'secret123',
            'redirect_uri' => 'http://localhost:8000/demo/callback',
            'base_url' => 'http://localhost:8000',
            'status' => 'active',
        ]);
        $app->roles()->sync([$teacherRole->id]);

        $response = $this->actingAs($student)->get(route('oauth.authorize', [
            'client_id' => $app->client_id,
            'redirect_uri' => $app->redirect_uri,
            'response_type' => 'code',
        ]));

        $response->assertStatus(403);

        $auditLog = AuditLog::latest()->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('sso_access_denied', $auditLog->activity);
        $this->assertTrue($auditLog->isSsoFailure());
    }
}
