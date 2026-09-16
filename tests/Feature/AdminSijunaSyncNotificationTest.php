<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SijunaApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSijunaSyncNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'], ['slug' => 'student']);
        Role::firstOrCreate(['name' => 'alumni', 'guard_name' => 'web'], ['slug' => 'alumni']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web'], ['slug' => 'teacher']);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->admin->assignRole($adminRole);
    }

    public function test_sync_notification_shows_counts_by_type_and_reasons_for_skipped_records(): void
    {
        $mockSijuna = $this->createMock(SijunaApiService::class);

        // Siswa: 1 aktif, 1 lulus (alumni), 1 tanpa NIS/external_id (skipped)
        $mockSijuna->method('getStudents')->willReturn([
            [
                'nis' => '8801',
                'nama' => 'Andi Siswa Aktif',
                'classroom' => 'X RPL 1',
                'email' => 'andi@siswa.sekolah.id',
                'graduated' => false,
            ],
            [
                'nis' => '8802',
                'nama' => 'Budi Alumni',
                'classroom' => 'XII RPL 1',
                'email' => 'budi@siswa.sekolah.id',
                'graduated' => true,
            ],
            [
                'nis' => null,
                'external_id' => '',
                'nama' => 'Candra Tanpa NIS',
                'classroom' => 'X RPL 2',
            ],
        ]);

        // Guru: 1 valid, 1 tanpa identitas (skipped)
        $mockSijuna->method('getTeachers')->willReturn([
            [
                'nip' => '19800101',
                'nama' => 'Pak Joko',
                'email' => 'joko@guru.sekolah.id',
            ],
            [
                'nip' => null,
                'external_id' => null,
                'email' => null,
                'nama' => 'Guru Tanpa Identitas',
            ],
        ]);

        $mockSijuna->method('getLastStudentError')->willReturn(null);
        $mockSijuna->method('usedStudentFallback')->willReturn(false);
        $mockSijuna->method('getLastTeacherError')->willReturn(null);
        $mockSijuna->method('usedTeacherFallback')->willReturn(false);

        $this->app->instance(SijunaApiService::class, $mockSijuna);

        $response = $this->actingAs($this->admin)->post(route('admin.sijuna.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('sync_report');

        $report = session('sync_report');
        $this->assertEquals(3, $report['total_fetched']); // 1 siswa + 1 alumni + 1 guru
        $this->assertEquals(1, $report['types']['siswa']);
        $this->assertEquals(1, $report['types']['alumni']);
        $this->assertEquals(1, $report['types']['guru']);
        $this->assertEquals(2, $report['skipped_count']);

        // Pastikan alasan dilewati tercatat dengan tepat
        $this->assertCount(2, $report['skipped_items']);
        $this->assertEquals('Candra Tanpa NIS', $report['skipped_items'][0]['identifier']);
        $this->assertEquals('NIS atau External ID kosong / tidak ditemukan', $report['skipped_items'][0]['reason']);

        $this->assertEquals('Guru Tanpa Identitas', $report['skipped_items'][1]['identifier']);
        $this->assertEquals('NIP, External ID, dan Email kosong / tidak ditemukan', $report['skipped_items'][1]['reason']);

        // Notifikasi success menyebutkan tipe data saja
        $successMsg = session('success');
        $this->assertStringContainsString('3 data', $successMsg);
        $this->assertStringContainsString('1 Siswa Aktif, 1 Alumni, 1 Guru', $successMsg);
        $this->assertStringContainsString('2 data belum diambil karena alasan validasi', $successMsg);
    }

    public function test_sync_captures_and_displays_warning_or_api_error(): void
    {
        $mockSijuna = $this->createMock(SijunaApiService::class);
        $mockSijuna->method('getStudents')->willReturn([]);
        $mockSijuna->method('getTeachers')->willReturn([]);
        $mockSijuna->method('getLastStudentError')->willReturn('Koneksi timeout ke SIJUNA');
        $mockSijuna->method('usedStudentFallback')->willReturn(true);
        $mockSijuna->method('getLastTeacherError')->willReturn(null);
        $mockSijuna->method('usedTeacherFallback')->willReturn(false);

        $this->app->instance(SijunaApiService::class, $mockSijuna);

        $response = $this->actingAs($this->admin)->post(route('admin.sijuna.sync'));

        $response->assertRedirect();
        $report = session('sync_report');
        $this->assertNotEmpty($report['warnings']);
        $this->assertContains('Koneksi timeout ke SIJUNA', $report['warnings']);
    }

    public function test_sijuna_index_page_displays_report_and_skipped_details(): void
    {
        $syncReport = [
            'total_fetched' => 45,
            'types' => [
                'siswa' => 30,
                'alumni' => 10,
                'guru' => 5,
            ],
            'skipped_count' => 2,
            'skipped_items' => [
                [
                    'identifier' => 'Data Siswa #12',
                    'reason' => 'NIS atau External ID kosong / tidak ditemukan',
                ],
                [
                    'identifier' => 'Data Guru #3',
                    'reason' => 'NIP, External ID, dan Email kosong / tidak ditemukan',
                ],
            ],
            'warnings' => ['Endpoint SIJUNA lambat merespons (warning)'],
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['sync_report' => $syncReport, 'success' => 'Sinkronisasi berhasil'])
            ->get(route('admin.sijuna.index'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rincian Sinkronisasi SIJUNA');
        $response->assertSee('Siswa Aktif');
        $response->assertSee('30');
        $response->assertSee('Alumni');
        $response->assertSee('10');
        $response->assertSee('Guru & Tenaga Pendidik', false);
        $response->assertSee('5');
        $response->assertSee('2 Data Belum Diambil / Dilewati:');
        $response->assertSee('Data Siswa #12');
        $response->assertSee('NIS atau External ID kosong / tidak ditemukan');
        $response->assertSee('Data Guru #3');
        $response->assertSee('NIP, External ID, dan Email kosong / tidak ditemukan');
        $response->assertSee('Peringatan / Error Sambungan SIJUNA API:');
        $response->assertSee('Endpoint SIJUNA lambat merespons (warning)');
    }
}
