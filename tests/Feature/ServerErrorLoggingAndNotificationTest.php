<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use App\Models\User;
use App\Notifications\SystemErrorOccurredNotification;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ServerErrorLoggingAndNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get Admin
        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@smkn1bangsri.sch.id'],
            [
                'name' => 'Admin Penguji',
                'username' => 'admin_test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Create or get Student
        $this->student = User::firstOrCreate(
            ['email' => 'student_test@smkn1bangsri.sch.id'],
            [
                'name' => 'Siswa Penguji',
                'username' => 'siswa_test',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'status' => 'active',
            ]
        );
    }

    public function test_500_server_error_is_automatically_recorded_and_notifies_admin(): void
    {
        $initialNotifCount = $this->admin->unreadNotifications()->count();

        // Register a temporary test route that throws a 500 error
        Route::get('/testing-error-route', function () {
            throw new Exception('Simulasi kegagalan database query di halaman portal siswa');
        });

        // Student makes request to the broken route
        $response = $this->actingAs($this->student)->get('/testing-error-route');

        $response->assertStatus(500);

        // Assert error log is saved in database
        $this->assertDatabaseHas('error_logs', [
            'status_code' => 500,
            'user_id' => $this->student->id,
            'user_role' => 'student',
        ]);

        $latestLog = ErrorLog::where('user_id', $this->student->id)->latest('id')->first();
        $this->assertNotNull($latestLog);
        $this->assertStringStartsWith('ERR-', $latestLog->incident_code);
        $this->assertEquals('Simulasi kegagalan database query di halaman portal siswa', $latestLog->message);

        // Assert notification was created for Admin
        $newNotifCount = $this->admin->unreadNotifications()->count();
        $this->assertGreaterThan($initialNotifCount, $newNotifCount);

        $latestNotif = $this->admin->unreadNotifications()->first();
        $this->assertEquals('system_error', $latestNotif->data['type']);
        $this->assertEquals($latestLog->incident_code, $latestNotif->data['incident_code']);
    }

    public function test_client_errors_such_as_404_are_not_logged_as_server_errors(): void
    {
        $initialErrorCount = ErrorLog::count();

        $response = $this->actingAs($this->student)->get('/halaman-yang-tidak-ada-sama-sekali-404');
        $response->assertStatus(404);

        $this->assertEquals($initialErrorCount, ErrorLog::count());
    }

    public function test_admin_can_view_error_logs_index_page(): void
    {
        // Create an error log
        $errorLog = ErrorLog::create([
            'incident_code' => 'ERR-TEST-VIEW',
            'status_code' => 500,
            'error_type' => 'RuntimeException',
            'message' => 'Pesan error untuk pengujian view index',
            'url' => 'http://localhost/siswa/dashboard',
            'method' => 'GET',
            'user_id' => $this->student->id,
            'user_role' => 'student',
            'status' => 'unresolved',
            'occurrence_count' => 1,
            'fingerprint' => 'test-fingerprint-view',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.error-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Log Error');
        $response->assertSee('ERR-TEST-VIEW');
        $response->assertSee('Pesan error untuk pengujian view index');
    }

    public function test_admin_can_view_error_log_detail(): void
    {
        $errorLog = ErrorLog::create([
            'incident_code' => 'ERR-TEST-DETAIL',
            'status_code' => 500,
            'error_type' => 'QueryException',
            'message' => 'Database connection timeout occurred during SSO',
            'url' => 'http://localhost/oauth/authorize',
            'method' => 'POST',
            'user_id' => $this->student->id,
            'user_role' => 'student',
            'status' => 'unresolved',
            'occurrence_count' => 3,
            'trace' => '#0 /app/Test.php(10): doSomething()',
            'fingerprint' => 'test-fingerprint-detail',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.error-logs.show', $errorLog->id));

        $response->assertStatus(200);
        $response->assertSee('ERR-TEST-DETAIL');
        $response->assertSee('Database connection timeout occurred during SSO');
        $response->assertSee('#0 /app/Test.php(10): doSomething()');
    }

    public function test_admin_can_mark_error_as_resolved(): void
    {
        $errorLog = ErrorLog::create([
            'incident_code' => 'ERR-TEST-RESOLVE',
            'status_code' => 500,
            'error_type' => 'ErrorException',
            'message' => 'Undetermined index test',
            'url' => 'http://localhost/test',
            'status' => 'unresolved',
            'occurrence_count' => 1,
            'fingerprint' => 'test-fingerprint-resolve',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.error-logs.resolve', $errorLog->id), [
            'resolution_notes' => 'Telah diperbaiki pengecekan null pada array.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('error_logs', [
            'id' => $errorLog->id,
            'status' => 'resolved',
            'resolved_by' => $this->admin->id,
            'resolution_notes' => 'Telah diperbaiki pengecekan null pada array.',
        ]);
    }

    public function test_admin_can_mark_notification_as_read(): void
    {
        $errorLog = ErrorLog::create([
            'incident_code' => 'ERR-TEST-NOTIF',
            'status_code' => 500,
            'error_type' => 'RuntimeException',
            'message' => 'Notif test message',
            'url' => 'http://localhost/test',
            'status' => 'unresolved',
            'occurrence_count' => 1,
            'fingerprint' => 'test-fingerprint-notif',
            'last_seen_at' => now(),
        ]);

        $this->admin->notify(new SystemErrorOccurredNotification($errorLog));
        $notification = $this->admin->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($this->admin)->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('admin.error-logs.show', $errorLog->id));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_regular_student_cannot_access_error_logs(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.error-logs.index'));

        // Should be forbidden or redirected
        $this->assertTrue(in_array($response->status(), [403, 302], true));
    }

    public function test_admin_can_trigger_test_500_error(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.error-logs.trigger-test'));

        $response->assertRedirect();

        $latestLog = ErrorLog::latest()->first();
        $this->assertStringContainsString('Simulasi Uji Coba Error Server 500', $latestLog->message);
    }
}
