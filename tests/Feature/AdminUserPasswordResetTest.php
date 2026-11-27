<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordSyncService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@smkn1bangsri.sch.id',
        ]);
        $this->admin->assignRole($adminRole);

        $this->student = User::factory()->create([
            'role' => 'student',
            'external_id' => '5445',
            'username' => '5445',
            'email' => '5445@sijuna.com',
            'password' => Hash::make('oldSecretPassword123'),
            'must_change_password' => false,
            'phone' => '081234567890',
        ]);
        $this->student->assignRole($studentRole);
    }

    public function test_admin_can_reset_user_password_to_default_with_must_change_flag(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'default',
                'must_change_password' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('reset_password_success');

        $sessionData = session('reset_password_success');
        $this->assertEquals('password', $sessionData['password']);
        $this->assertTrue($sessionData['must_change']);
        $this->assertEquals($this->student->name, $sessionData['user_name']);

        $this->student->refresh();
        $this->assertTrue(Hash::check('password', $this->student->password));
        $this->assertTrue($this->student->must_change_password);
    }

    public function test_admin_can_reset_user_password_to_random_generated_password(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'random',
                'must_change_password' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('reset_password_success');

        $sessionData = session('reset_password_success');
        $generatedPass = $sessionData['password'];
        $this->assertStringStartsWith('Pintu#', $generatedPass);

        $this->student->refresh();
        $this->assertTrue(Hash::check($generatedPass, $this->student->password));
        $this->assertTrue($this->student->must_change_password);
    }

    public function test_admin_can_reset_user_password_to_custom_password(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'custom',
                'password' => 'CustomSandiBaru2026!',
                'must_change_password' => '0',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('reset_password_success');

        $this->student->refresh();
        $this->assertTrue(Hash::check('CustomSandiBaru2026!', $this->student->password));
        $this->assertFalse($this->student->must_change_password);
    }

    public function test_admin_reset_password_validates_custom_password_length(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'custom',
                'password' => 'short',
            ]);

        $response->assertSessionHasErrors(['password']);
        $this->student->refresh();
        $this->assertTrue(Hash::check('oldSecretPassword123', $this->student->password));
    }

    public function test_admin_reset_password_logs_audit_event(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'default',
                'must_change_password' => '1',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'activity' => 'admin_reset_user_password',
            'user_id' => $this->student->id,
        ]);
    }

    public function test_non_admin_cannot_reset_user_password(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'default',
            ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_reset_user_password(): void
    {
        $response = $this->post(route('admin.users.reset-password', $this->student), [
            'reset_type' => 'default',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_send_whatsapp_notification_during_reset(): void
    {
        $mockWhatsApp = $this->createMock(WhatsAppService::class);
        $mockWhatsApp->expects($this->once())
            ->method('formatPhoneNumber')
            ->with('081234567890')
            ->willReturn('6281234567890');

        $mockWhatsApp->expects($this->once())
            ->method('sendMessage')
            ->with(
                '6281234567890',
                $this->stringContains('PEMBERITAHUAN RESET KATA SANDI SIPINTU')
            )
            ->willReturn(['success' => true]);

        $this->app->instance(WhatsAppService::class, $mockWhatsApp);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.reset-password', $this->student), [
                'reset_type' => 'default',
                'must_change_password' => '1',
                'send_whatsapp' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('reset_password_success');
        $this->assertTrue(session('reset_password_success')['wa_sent']);
    }

    public function test_admin_can_update_must_change_password_from_user_edit(): void
    {
        $this->assertFalse($this->student->must_change_password);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->student), [
                'name' => $this->student->name,
                'email' => $this->student->email,
                'role' => 'student',
                'status' => 'active',
                'password' => 'NewManualPassword123',
                'must_change_password' => '1',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->student->refresh();
        $this->assertTrue(Hash::check('NewManualPassword123', $this->student->password));
        $this->assertTrue($this->student->must_change_password);
    }
}
