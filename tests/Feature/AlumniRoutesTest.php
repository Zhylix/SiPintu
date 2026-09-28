<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AlumniRoutesTest extends TestCase
{
    protected function getAlumniUser(): User
    {
        $alumni = User::where('role', 'alumni')->first();
        if (! $alumni) {
            $jurusan = Jurusan::first();
            $alumni = User::create([
                'name' => 'Alumni Test',
                'username' => 'alumni_test',
                'external_id' => 'ALUMNI001',
                'email' => 'alumnitest@smkn1bangsri.sch.id',
                'password' => Hash::make('password123'),
                'role' => 'alumni',
                'status' => 'active',
                'classroom' => 'XII PPLG 1',
                'jurusan_id' => $jurusan?->id,
            ]);
        }

        return $alumni;
    }

    public function test_alumni_can_access_alumni_dashboard(): void
    {
        $alumni = $this->getAlumniUser();

        $response = $this->actingAs($alumni)->get(route('alumni.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Portal Alumni');
    }

    public function test_alumni_can_access_alumni_apps(): void
    {
        $alumni = $this->getAlumniUser();

        $response = $this->actingAs($alumni)->get(route('alumni.apps'));

        $response->assertStatus(200);
        $response->assertSee('Katalog Aplikasi Alumni');
    }

    public function test_public_alumni_api_returns_paginated_alumni_data(): void
    {
        $response = $this->getJson(route('api.v1.alumni'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'data' => [
                '*' => ['id', 'external_id', 'nis', 'name', 'email', 'role', 'classroom', 'jurusan'],
            ],
        ]);
    }

    public function test_public_alumni_api_returns_single_alumni_detail(): void
    {
        $alumni = $this->getAlumniUser();

        $response = $this->getJson(route('api.v1.alumni_detail', ['identifier' => $alumni->external_id]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'name' => $alumni->name,
                'role' => 'alumni',
            ],
        ]);
    }

    public function test_home_and_dashboard_redirects_alumni_to_alumni_dashboard(): void
    {
        $alumni = $this->getAlumniUser();

        $response = $this->actingAs($alumni)->get('/');
        $response->assertRedirect(route('alumni.dashboard'));

        $responseDash = $this->actingAs($alumni)->get('/dashboard');
        $responseDash->assertRedirect(route('alumni.dashboard'));
    }
}
