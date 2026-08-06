<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_password(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'username' => '12345',
            'external_id' => '12345',
            'role' => 'student',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        $student->assignRole($studentRole);

        $response = $this->post(route('login'), [
            'nis' => '12345',
            'password' => '',
            'account_type' => 'siswa',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    public function test_student_login_fails_with_wrong_password(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'username' => '12345',
            'external_id' => '12345',
            'role' => 'student',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        $student->assignRole($studentRole);

        $response = $this->post(route('login'), [
            'nis' => '12345',
            'password' => 'wrongpassword',
            'account_type' => 'siswa',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    public function test_student_login_succeeds_with_correct_password(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student = User::factory()->create([
            'username' => '12345',
            'external_id' => '12345',
            'role' => 'student',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        $student->assignRole($studentRole);

        $response = $this->post(route('login'), [
            'nis' => '12345',
            'password' => 'secret123',
            'account_type' => 'siswa',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_role_mismatch_returns_error_and_retains_input(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher = User::factory()->create([
            'email' => 'guru@sekolah.id',
            'username' => '19850101',
            'role' => 'teacher',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        $teacher->assignRole($teacherRole);

        // Teacher trying to log in on Siswa tab
        $response = $this->post(route('login'), [
            'nis' => '19850101',
            'password' => 'secret123',
            'account_type' => 'siswa',
        ]);

        $response->assertSessionHasErrors(['nis']);
        $response->assertSessionHasInput('nis', '19850101');
        $this->assertGuest();
    }

    public function test_admin_login_succeeds_from_any_tab(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create([
            'email' => 'admin@gateway.sekolah.id',
            'username' => 'admin',
            'role' => 'admin',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);
        $admin->assignRole($adminRole);

        $response = $this->post(route('login'), [
            'nis' => 'admin@gateway.sekolah.id',
            'password' => 'secret123',
            'account_type' => 'siswa',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }
}
