<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAlumniTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_separated_alumni_and_student_counts(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'], ['slug' => 'student']);
        $alumniRole = Role::firstOrCreate(['name' => 'alumni', 'guard_name' => 'web'], ['slug' => 'alumni']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@smkn1bangsri.sch.id',
        ]);
        $admin->assignRole($adminRole);

        // Create 3 active students
        User::factory()->count(3)->create([
            'role' => 'student',
        ])->each(fn ($u) => $u->assignRole($studentRole));

        // Create 2 alumni
        User::factory()->count(2)->create([
            'role' => 'alumni',
        ])->each(fn ($u) => $u->assignRole($alumniRole));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['students_count'])
                && isset($stats['alumni_count'])
                && $stats['students_count'] === 3
                && $stats['alumni_count'] === 2;
        });

        $response->assertSee('Total Alumni');
        $response->assertSee('Total Pengguna');
    }
}
