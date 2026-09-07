<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserDataSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Application $downstreamApp;

    protected User $student;

    protected Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->downstreamApp = Application::create([
            'name' => 'Aplikasi Ujian CBT',
            'slug' => 'aplikasi-cbt',
            'client_id' => 'app_cbt_123',
            'client_secret' => 'sec_cbt_supersecret',
            'base_url' => 'http://localhost:8001',
            'redirect_uri' => 'http://localhost:8001/oauth/callback',
            'status' => 'active',
        ]);
        $this->downstreamApp->roles()->attach($this->studentRole);

        $this->student = User::factory()->create([
            'name' => 'Ahmad Santoso',
            'email' => 'ahmad@smkn1bangsri.sch.id',
            'username' => '123456',
            'external_id' => '123456',
            'role' => 'student',
            'classroom' => 'XI RPL 1',
            'phone' => '08123456789',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
        $this->student->assignRole($this->studentRole);
    }

    public function test_user_observer_automatically_broadcasts_profile_update_to_downstream(): void
    {
        Http::fake([
            'http://localhost:8001/api/sipintu/sync-user' => Http::response(['status' => 'success'], 200),
        ]);

        // Update user's name, phone, and classroom
        $this->student->update([
            'name' => 'Ahmad Santoso, S.Kom',
            'phone' => '089988776655',
            'classroom' => 'XII RPL 1',
        ]);

        Http::assertSent(function (Request $request) {
            if ($request->url() !== 'http://localhost:8001/api/sipintu/sync-user') {
                return false;
            }

            $body = $request->data();

            // Verify payload structure
            $matchesEvent = ($body['event'] ?? null) === 'user.updated';
            $matchesName = ($body['user']['name'] ?? null) === 'Ahmad Santoso, S.Kom';
            $matchesPhone = ($body['user']['phone'] ?? null) === '089988776655';
            $matchesClass = ($body['user']['classroom'] ?? null) === 'XII RPL 1';

            // Verify changed_fields and previous values
            $changedFields = $body['changed_fields'] ?? [];
            $hasChangedFields = in_array('name', $changedFields) && in_array('phone', $changedFields) && in_array('classroom', $changedFields);

            $previous = $body['previous'] ?? [];
            $hasPreviousName = ($previous['name'] ?? null) === 'Ahmad Santoso';

            // Verify HMAC Signature header
            $signatureHeader = $request->header('X-SiPintu-Signature')[0] ?? null;
            $rawContent = $request->body();
            $expectedSignature = hash_hmac('sha256', $rawContent, 'sec_cbt_supersecret');
            $signatureValid = hash_equals($expectedSignature, (string) $signatureHeader);

            return $matchesEvent && $matchesName && $matchesPhone && $matchesClass && $hasChangedFields && $hasPreviousName && $signatureValid;
        });
    }

    public function test_user_observer_sends_previous_email_when_email_is_changed(): void
    {
        Http::fake([
            'http://localhost:8001/api/sipintu/sync-user' => Http::response(['status' => 'success'], 200),
        ]);

        $this->student->update([
            'email' => 'ahmad.baru@smkn1bangsri.sch.id',
        ]);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return ($body['user']['email'] ?? null) === 'ahmad.baru@smkn1bangsri.sch.id'
                && ($body['previous']['email'] ?? null) === 'ahmad@smkn1bangsri.sch.id';
        });
    }

    public function test_user_password_update_falls_back_to_legacy_endpoint_if_sync_user_is_404(): void
    {
        Http::fake([
            'http://localhost:8001/api/sipintu/sync-user' => Http::response('Not Found', 404),
            'http://localhost:8001/api/sipintu/sync-password' => Http::response(['status' => 'synced'], 200),
        ]);

        $this->student->update([
            'password' => Hash::make('brandnewpassword456'),
        ]);

        // Should first attempt /api/sipintu/sync-user, and upon 404 fallback to /api/sipintu/sync-password
        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://localhost:8001/api/sipintu/sync-password'
                && ($request->data()['email'] ?? null) === 'ahmad@smkn1bangsri.sch.id';
        });
    }

    public function test_admin_user_password_is_exempt_from_downstream_sync_payload(): void
    {
        Http::fake([
            'http://localhost:8001/api/sipintu/sync-user' => Http::response(['status' => 'success'], 200),
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create([
            'name' => 'Admin Utama',
            'email' => 'admin@smkn1bangsri.sch.id',
            'role' => 'admin',
            'password' => Hash::make('adminsecret789'),
        ]);
        $admin->assignRole($adminRole);

        $admin->update([
            'name' => 'Admin Utama Updated',
            'password' => Hash::make('newadminsecret999'),
        ]);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return ($body['user']['name'] ?? null) === 'Admin Utama Updated'
                && ($body['user']['password_change_policy'] ?? null) === 'ADMIN_EXEMPT'
                && empty($body['user']['password']);
        });
    }

    public function test_cli_command_sync_user_broadcasts_successfully(): void
    {
        Http::fake([
            'http://localhost:8001/api/sipintu/sync-user' => Http::response(['status' => 'success'], 200),
        ]);

        $exitCode = Artisan::call('sipintu:sync-user', [
            'identifier' => $this->student->email,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Aplikasi Ujian CBT', $output);
        $this->assertStringContainsString('SYNCED (200)', $output);
    }

    public function test_demo_simulator_sync_user_endpoint_updates_active_demo_session(): void
    {
        // Setup demo session
        session()->put('demo_session_cbt', [
            'user' => [
                'id' => (string) $this->student->id,
                'name' => 'Ahmad Santoso Lama',
                'email' => 'ahmad@smkn1bangsri.sch.id',
                'role' => 'student',
            ],
            'synced_password' => 'oldpass',
        ]);

        $payload = [
            'event' => 'user.updated',
            'user' => [
                'id' => (string) $this->student->id,
                'name' => 'Ahmad Santoso Terkini',
                'email' => 'ahmad@smkn1bangsri.sch.id',
                'role' => 'student',
                'phone' => '0877112233',
                'password' => 'newhashedpassword',
            ],
            'changed_fields' => ['name', 'phone', 'password'],
            'previous' => ['name' => 'Ahmad Santoso Lama'],
        ];

        $response = $this->postJson(route('demo.sync-user', ['appSlug' => 'cbt']), $payload);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $session = session()->get('demo_session_cbt');
        $this->assertEquals('Ahmad Santoso Terkini', $session['user']['name']);
        $this->assertEquals('newhashedpassword', $session['synced_password']);
        $this->assertNotEmpty($session['last_webhook_synced_at']);
    }
}
