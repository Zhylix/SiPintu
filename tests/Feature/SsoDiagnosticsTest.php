<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use App\Services\SsoDiagnosticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_diagnostics_service_detects_inactive_app_and_missing_roles(): void
    {
        $app = Application::create([
            'name' => 'Aplikasi Nonaktif',
            'slug' => 'aplikasi-nonaktif',
            'client_id' => 'app_testinactive',
            'client_secret' => Hash::make('sec_secret123'),
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'inactive',
        ]);

        $service = new SsoDiagnosticsService;
        $diagnosis = $service->diagnose($app);

        $this->assertEquals('CRITICAL', $diagnosis['overall_status']);
        $this->assertNotEmpty($diagnosis['issues']);

        // Check if issues contain APP_INACTIVE and NO_ROLES_ASSIGNED
        $issueIds = array_column($diagnosis['issues'], 'id');
        $this->assertContains('APP_INACTIVE', $issueIds);
        $this->assertContains('NO_ROLES_ASSIGNED', $issueIds);
    }

    public function test_diagnostics_service_checks_connectivity_and_remediation(): void
    {
        Http::fake([
            'http://localhost:8001' => Http::response('OK', 200),
            'http://localhost:8001/health' => Http::response(['status' => 'ok'], 200),
            'http://localhost:8001/oauth/callback' => Http::response('Callback Ready', 200),
            'http://localhost:8001/api/sipintu/sync-password' => Http::response(['status' => 'synced'], 200),
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $app = Application::create([
            'name' => 'Aplikasi Sehat',
            'slug' => 'aplikasi-sehat',
            'client_id' => 'app_testhealthy',
            'client_secret' => Hash::make('sec_secret123'),
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);
        $app->roles()->attach($studentRole);

        $service = new SsoDiagnosticsService;
        $diagnosis = $service->diagnose($app);

        $this->assertEquals('HEALTHY', $diagnosis['overall_status']);
        $this->assertEquals(100, $diagnosis['health_score']);
        $this->assertEmpty($diagnosis['issues']);
    }

    public function test_diagnostics_service_checks_connectivity_with_modern_user_sync(): void
    {
        Http::fake([
            'http://localhost:8001' => Http::response('OK', 200),
            'http://localhost:8001/health' => Http::response(['status' => 'ok'], 200),
            'http://localhost:8001/oauth/callback' => Http::response('Callback Ready', 200),
            'http://localhost:8001/api/sipintu/sync-user' => Http::response(['status' => 'synced'], 200),
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $app = Application::create([
            'name' => 'Aplikasi Modern',
            'slug' => 'aplikasi-modern',
            'client_id' => 'app_testmodern',
            'client_secret' => Hash::make('sec_secret123'),
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);
        $app->roles()->attach($studentRole);

        $service = new SsoDiagnosticsService;
        $diagnosis = $service->diagnose($app);

        $this->assertEquals('HEALTHY', $diagnosis['overall_status']);
        $this->assertEquals(100, $diagnosis['health_score']);
        $this->assertEmpty($diagnosis['issues']);
    }

    public function test_diagnostics_service_detects_invalid_base_url(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $app = Application::create([
            'name' => 'Aplikasi Invalid URL',
            'slug' => 'aplikasi-invalid-url',
            'client_id' => 'app_testinvalid',
            'client_secret' => Hash::make('sec_secret123'),
            'base_url' => 'invalid-url',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);
        $app->roles()->attach($studentRole);

        $service = new SsoDiagnosticsService;
        $diagnosis = $service->diagnose($app);

        $this->assertEquals('CRITICAL', $diagnosis['overall_status']);
        $issueIds = array_column($diagnosis['issues'], 'id');
        $this->assertContains('INVALID_BASE_URL_FORMAT', $issueIds);
    }

    public function test_admin_can_run_sso_diagnosis_api_endpoint(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        $app = Application::create([
            'name' => 'Test App Endpoint',
            'slug' => 'test-app-endpoint',
            'client_id' => 'app_testendpoint',
            'client_secret' => 'sec_secret123',
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.monitoring.diagnose-sso'), [
                'client_id' => 'app_testendpoint',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'data' => [
                'application',
                'overall_status',
                'health_score',
                'checks',
                'issues',
            ],
        ]);
    }

    public function test_admin_can_run_batch_sso_diagnosis_api_endpoint(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        Application::create([
            'name' => 'App 1',
            'slug' => 'app-1',
            'client_id' => 'app_batch1',
            'client_secret' => 'sec_1',
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.monitoring.diagnose-all-sso'));

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonStructure([
            'status',
            'data' => [
                'total_applications',
                'healthy_count',
                'warning_count',
                'critical_count',
                'results',
            ],
        ]);
    }

    public function test_cli_command_runs_successfully(): void
    {
        Http::fake([
            '*' => Http::response('OK', 200),
        ]);

        Application::create([
            'name' => 'CLI Test App',
            'slug' => 'cli-test-app',
            'client_id' => 'app_cli_test',
            'client_secret' => 'sec_cli',
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);

        $this->artisan('sipintu:sso-diagnose', ['--all' => true])
            ->assertSuccessful();
    }
}
