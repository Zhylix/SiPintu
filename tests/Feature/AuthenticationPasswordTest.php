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
            'identity' => '12345',
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
            'identity' => '12345',
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
            'identity' => '12345',
            'password' => 'secret123',
            'account_type' => 'siswa',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }
}
