<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SijunaSyncIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_password_change_does_not_hit_sijuna_even_if_registered_as_downstream_app(): void
    {
        Http::fake();

        // Downstream app configured with SIJUNA URL
        $sijunaApp = Application::create([
            'name' => 'SIJUNA App',
            'slug' => 'sijuna',
            'client_id' => 'client_sijuna_123',
            'client_secret' => 'secret_sijuna_456',
            'base_url' => 'https://sijuna.com',
            'redirect_uri' => 'https://sijuna.com/callback',
            'status' => 'active',
            'scopes' => 'openid profile email',
            'allow_password_sync' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('oldpassword123'),
            'phone' => '081234567890',
        ]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'phone' => '081234567890',
        ]);

        $response->assertSessionHasNoErrors();

        // Assert that NO HTTP request was ever sent to sijuna.com
        Http::assertNothingSent();
    }

    public function test_student_login_does_not_hit_sijuna(): void
    {
        Http::fake();

        // Attempting login with unknown student NIS
        $response = $this->post('/login', [
            'account_type' => 'siswa',
            'identity' => '999999999',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('nis');

        // Assert that NO HTTP request was made to SIJUNA API during login attempt
        Http::assertNothingSent();
    }

    public function test_api_students_endpoint_returns_local_db_without_hitting_sijuna(): void
    {
        Http::fake();

        $student = User::factory()->create([
            'role' => 'student',
            'username' => '12345',
            'external_id' => '12345',
            'name' => 'Budi Santoso',
            'email' => 'budi@sekolah.sch.id',
            'classroom' => 'XII RPL 1',
            'status' => 'active',
        ]);

        // Create an active application for bearer middleware
        $app = Application::create([
            'name' => 'Portal Guruku',
            'slug' => 'guruku',
            'client_id' => 'client_guruku_123',
            'client_secret' => 'secret_guruku_456',
            'base_url' => 'https://guruku.test',
            'redirect_uri' => 'https://guruku.test/callback',
            'status' => 'active',
            'scopes' => 'openid profile email',
        ]);

        $response = $this->withHeaders([
            'X-Client-ID' => $app->client_id,
            'X-Client-Secret' => $app->client_secret,
        ])->getJson('/api/v1/sijuna/students?nis=12345');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Budi Santoso');
        $response->assertJsonPath('source', 'SiPintu Database (Synced from SIJUNA)');

        // Assert that SIJUNA external API was never called
        Http::assertNothingSent();
    }

    public function test_downstream_can_retrieve_teachers_and_dudi_via_gateway(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'username' => '198501012010011001',
            'external_id' => '198501012010011001',
            'name' => 'Pak Guru Hebat',
            'email' => 'guru@sekolah.sch.id',
            'status' => 'active',
        ]);

        $dudi = User::factory()->create([
            'role' => 'dudi',
            'username' => 'DUDI001',
            'external_id' => 'DUDI001',
            'name' => 'PT Teknologi Unggul',
            'email' => 'hrd@unggul.co.id',
            'phone' => '08123456789',
            'status' => 'active',
        ]);

        $app = Application::create([
            'name' => 'SIMONGAN PKL',
            'slug' => 'simongan',
            'client_id' => 'client_simongan_789',
            'client_secret' => 'secret_simongan_101',
            'base_url' => 'https://simongan.test',
            'redirect_uri' => 'https://simongan.test/callback',
            'status' => 'active',
            'scopes' => 'openid profile email',
        ]);

        // 1. Test teachers endpoint
        $resTeachers = $this->withHeaders([
            'X-Client-ID' => $app->client_id,
            'X-Client-Secret' => $app->client_secret,
        ])->getJson('/api/v1/sijuna/teachers?nip=198501012010011001');

        $resTeachers->assertOk();
        $resTeachers->assertJsonPath('data.name', 'Pak Guru Hebat');

        // 2. Test teachers list with pagination
        $resTeachersList = $this->withHeaders([
            'X-Client-ID' => $app->client_id,
            'X-Client-Secret' => $app->client_secret,
        ])->getJson('/api/v1/sijuna/teachers?per_page=10&page=1');

        $resTeachersList->assertOk();
        $resTeachersList->assertJsonStructure([
            'status',
            'source',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'data',
        ]);

        // 3. Test dudi endpoint
        $resDudi = $this->withHeaders([
            'X-Client-ID' => $app->client_id,
            'X-Client-Secret' => $app->client_secret,
        ])->getJson('/api/v1/sijuna/dudi?dudi_code=DUDI001');

        $resDudi->assertOk();
        $resDudi->assertJsonPath('data.nama_perusahaan', 'PT Teknologi Unggul');

        // 4. Test dudi list with pagination
        $resDudiList = $this->withHeaders([
            'X-Client-ID' => $app->client_id,
            'X-Client-Secret' => $app->client_secret,
        ])->getJson('/api/v1/dudi?per_page=10&page=1');

        $resDudiList->assertOk();
        $resDudiList->assertJsonStructure([
            'status',
            'source',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'data',
        ]);
    }
}
