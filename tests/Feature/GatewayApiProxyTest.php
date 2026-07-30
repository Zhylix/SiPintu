<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayApiProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/sijuna/students');
        $response->assertStatus(401);
    }

    public function test_api_allows_access_via_client_credentials(): void
    {
        $app = Application::create([
            'name' => 'Test App',
            'slug' => 'test-app',
            'base_url' => 'http://localhost:3000',
            'redirect_uri' => 'http://localhost:3000/callback',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'X-Client-ID' => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->getJson('/api/v1/sijuna/students');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'source', 'count', 'data']);
    }

    public function test_api_can_query_student_by_nis(): void
    {
        Application::create([
            'name' => 'Test App',
            'slug' => 'test-app',
            'base_url' => 'http://localhost:3000',
            'redirect_uri' => 'http://localhost:3000/callback',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Siswa Test',
            'email' => 'siswa@test.com',
            'username' => '1234567890',
            'external_id' => '1234567890',
            'password' => 'secret123',
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'X-Client-ID' => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->getJson('/api/v1/sijuna/students?nis=1234567890');

        $response->assertStatus(200);
        $response->assertJsonPath('data.nama', 'Siswa Test');
    }
}
