<?php

namespace Tests\Feature;

use App\Jobs\SyncSijunaStudentsJob;
use App\Models\Application;
use App\Models\Jurusan;
use App\Models\OAuthAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Services\SijunaApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlumniJurusanGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'], ['slug' => 'student']);
        Role::firstOrCreate(['name' => 'alumni', 'guard_name' => 'web'], ['slug' => 'alumni']);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->admin->assignRole($adminRole);
    }

    /**
     * Test akurasi ekstraksi regex normalisasi ke 5 Jurusan Resmi (PPLG, TO, AKL, PM, MPLB)
     */
    public function test_regex_extracts_and_normalizes_to_five_canonical_jurusans(): void
    {
        // PPLG (PPLG, PPL, RPL, SIJA)
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('XII PPLG 1'));
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('XII PPL 2'));
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('XII RPL 1'));
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('X PPLG 2'));
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('12 PPLG 1'));

        // TO (TO, TBSM, TKRO)
        $this->assertEquals('TO', Jurusan::extractKodeJurusan('XII TO 1'));
        $this->assertEquals('TO', Jurusan::extractKodeJurusan('XII TO 2'));
        $this->assertEquals('TO', Jurusan::extractKodeJurusan('XII TBSM 1'));
        $this->assertEquals('TO', Jurusan::extractKodeJurusan('XI TKRO 2'));

        // AKL (AKL, AK)
        $this->assertEquals('AKL', Jurusan::extractKodeJurusan('XII AKL 1'));
        $this->assertEquals('AKL', Jurusan::extractKodeJurusan('XII AKL 2'));
        $this->assertEquals('AKL', Jurusan::extractKodeJurusan('XI AKL 3'));

        // PM (PM, BDP)
        $this->assertEquals('PM', Jurusan::extractKodeJurusan('XII PM 1'));
        $this->assertEquals('PM', Jurusan::extractKodeJurusan('XII PM 2'));
        $this->assertEquals('PM', Jurusan::extractKodeJurusan('X BDP 1'));

        // MPLB (MPLB, OTKP, AP)
        $this->assertEquals('MPLB', Jurusan::extractKodeJurusan('XII MPLB 1'));
        $this->assertEquals('MPLB', Jurusan::extractKodeJurusan('XII MPLB 2'));
        $this->assertEquals('MPLB', Jurusan::extractKodeJurusan('XII MPLB 3'));
        $this->assertEquals('MPLB', Jurusan::extractKodeJurusan('XII OTKP 1'));

        // Format dengan tanda kurung dan awalan
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('XII PPLG 1 (Lulus)'));
        $this->assertEquals('PPLG', Jurusan::extractKodeJurusan('Alumni RPL'));

        // Null atau string kosong
        $this->assertNull(Jurusan::extractKodeJurusan(null));
        $this->assertNull(Jurusan::extractKodeJurusan(''));
    }

    /**
     * Test relasi Eloquent antara Jurusan dan User (alumni dan students)
     */
    public function test_jurusan_and_user_eloquent_relationships(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->firstOrFail();
        $akl = Jurusan::where('kode_jurusan', 'AKL')->firstOrFail();

        $alumniPpl = User::factory()->create([
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        $studentPpl = User::factory()->create([
            'role' => 'student',
            'classroom' => 'X PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        $alumniAkl = User::factory()->create([
            'role' => 'alumni',
            'classroom' => 'XII AKL 1',
            'jurusan_id' => $akl->id,
        ]);

        // Verifikasi belongsTo
        $this->assertEquals($ppl->id, $alumniPpl->jurusan->id);
        $this->assertEquals('Pengembangan Perangkat Lunak dan Gim', $alumniPpl->jurusan->nama_jurusan);

        // Verifikasi hasMany alumni
        $this->assertTrue($ppl->alumni->contains($alumniPpl));
        $this->assertFalse($ppl->alumni->contains($studentPpl));
        $this->assertFalse($ppl->alumni->contains($alumniAkl));

        // Verifikasi hasMany students
        $this->assertTrue($ppl->students->contains($studentPpl));
        $this->assertFalse($ppl->students->contains($alumniPpl));
    }

    /**
     * Test SyncSijunaStudentsJob otomatis mengaitkan alumni ke jurusan yang sesuai
     */
    public function test_sync_sijuna_job_assigns_jurusan_id_to_alumni_and_students(): void
    {
        $mockSijuna = $this->createMock(SijunaApiService::class);
        $mockSijuna->method('getStudents')->willReturn([
            [
                'id' => '1',
                'nis' => '7001',
                'nama' => 'Alumni PPL',
                'classroom' => 'XII PPLG 1',
                'email' => 'alumni.ppl@sijuna.sch.id',
                'graduated' => true,
            ],
            [
                'id' => '2',
                'nis' => '7002',
                'nama' => 'Alumni TO',
                'classroom' => 'XII TO 2',
                'email' => 'alumni.to@sijuna.sch.id',
                'graduated' => true,
            ],
            [
                'id' => '3',
                'nis' => '7003',
                'nama' => 'Alumni AKL',
                'classroom' => 'XII AKL 1',
                'email' => 'alumni.akl@sijuna.sch.id',
                'graduated' => true,
            ],
            [
                'id' => '4',
                'nis' => '7004',
                'nama' => 'Alumni PM',
                'classroom' => 'XII PM 1',
                'email' => 'alumni.pm@sijuna.sch.id',
                'graduated' => true,
            ],
            [
                'id' => '5',
                'nis' => '7005',
                'nama' => 'Alumni MPLB',
                'classroom' => 'XII MPLB 3',
                'email' => 'alumni.mplb@sijuna.sch.id',
                'graduated' => true,
            ],
            [
                'id' => '6',
                'nis' => '7006',
                'nama' => 'Rian (Alumni RPL)',
                'classroom' => null, // null classroom tapi ada (Alumni RPL) di nama
                'email' => 'rian.legacy@sijuna.sch.id',
                'graduated' => true,
            ],
        ]);

        $mockSijuna->method('getLastStudentError')->willReturn(null);
        $mockSijuna->method('usedStudentFallback')->willReturn(false);

        $job = new SyncSijunaStudentsJob();
        $job->handle($mockSijuna);

        $pplJurusan = Jurusan::where('kode_jurusan', 'PPLG')->first();
        $toJurusan = Jurusan::where('kode_jurusan', 'TO')->first();
        $aklJurusan = Jurusan::where('kode_jurusan', 'AKL')->first();
        $pmJurusan = Jurusan::where('kode_jurusan', 'PM')->first();
        $mplbJurusan = Jurusan::where('kode_jurusan', 'MPLB')->first();

        $userPpl = User::where('external_id', '7001')->first();
        $this->assertNotNull($userPpl);
        $this->assertEquals($pplJurusan->id, $userPpl->jurusan_id);

        $userTo = User::where('external_id', '7002')->first();
        $this->assertNotNull($userTo);
        $this->assertEquals($toJurusan->id, $userTo->jurusan_id);

        $userAkl = User::where('external_id', '7003')->first();
        $this->assertNotNull($userAkl);
        $this->assertEquals($aklJurusan->id, $userAkl->jurusan_id);

        $userPm = User::where('external_id', '7004')->first();
        $this->assertNotNull($userPm);
        $this->assertEquals($pmJurusan->id, $userPm->jurusan_id);

        $userMplb = User::where('external_id', '7005')->first();
        $this->assertNotNull($userMplb);
        $this->assertEquals($mplbJurusan->id, $userMplb->jurusan_id);

        $userLegacy = User::where('external_id', '7006')->first();
        $this->assertNotNull($userLegacy);
        $this->assertEquals($pplJurusan->id, $userLegacy->jurusan_id);
    }

    /**
     * Test admin dapat mengakses halaman pengelompokan alumni per jurusan dan melihat tabel
     */
    public function test_admin_can_view_jurusan_grouping_page(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'Alumni PPL Test',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jurusan.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengelompokan Alumni per Jurusan');
        $response->assertSee('PPLG');
        $response->assertSee('TO');
        $response->assertSee('AKL');
        $response->assertSee('PM');
        $response->assertSee('MPLB');
        $response->assertSee('Alumni PPL Test');
    }

    /**
     * Test filter alumni per jurusan di halaman admin jurusan
     */
    public function test_admin_can_filter_alumni_by_jurusan(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();
        $akl = Jurusan::where('kode_jurusan', 'AKL')->first();

        User::factory()->create([
            'name' => 'Budi Siswa PPL',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        User::factory()->create([
            'name' => 'Siti Siswa AKL',
            'role' => 'alumni',
            'classroom' => 'XII AKL 2',
            'jurusan_id' => $akl->id,
        ]);

        // Filter PPLG
        $responsePpl = $this->actingAs($this->admin)->get(route('admin.jurusan.index', ['jurusan' => 'PPLG']));
        $responsePpl->assertStatus(200);
        $responsePpl->assertSee('Budi Siswa PPL');
        $responsePpl->assertDontSee('Siti Siswa AKL');

        // Filter AKL
        $responseAkl = $this->actingAs($this->admin)->get(route('admin.jurusan.index', ['jurusan' => 'AKL']));
        $responseAkl->assertStatus(200);
        $responseAkl->assertSee('Siti Siswa AKL');
        $responseAkl->assertDontSee('Budi Siswa PPL');
    }

    /**
     * Test admin resync action mengelompokkan ulang seluruh alumni
     */
    public function test_admin_can_trigger_resync(): void
    {
        // Buat alumni tanpa jurusan_id tapi punya classroom
        $alumni = User::factory()->create([
            'name' => 'Alumni Belum Terpetakan',
            'role' => 'alumni',
            'classroom' => 'XII TO 1',
            'jurusan_id' => null,
        ]);

        $this->assertNull($alumni->jurusan_id);

        $response = $this->actingAs($this->admin)->post(route('admin.jurusan.resync'));

        $response->assertRedirect(route('admin.jurusan.index'));
        $response->assertSessionHas('success');

        $alumni->refresh();
        $toJurusan = Jurusan::where('kode_jurusan', 'TO')->first();
        $this->assertEquals($toJurusan->id, $alumni->jurusan_id);
    }

    /**
     * Test downstream application dapat mengambil daftar 5 jurusan melalui API GET /api/v1/jurusans
     */
    public function test_downstream_api_can_get_jurusans_list(): void
    {
        $response = $this->getJson(route('api.v1.jurusans'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => ['id', 'kode_jurusan', 'nama_jurusan', 'deskripsi', 'total_alumni', 'total_siswa'],
            ],
        ]);

        $this->assertEquals(5, $response->json('count'));
        $kodes = collect($response->json('data'))->pluck('kode_jurusan')->all();
        $this->assertEquals(['PPLG', 'TO', 'AKL', 'PM', 'MPLB'], $kodes);
    }

    /**
     * Test downstream application dapat mengambil detail satu jurusan via GET /api/v1/jurusans/{kode}
     */
    public function test_downstream_api_can_get_jurusan_detail(): void
    {
        $response = $this->getJson(route('api.v1.jurusan_detail', ['kode' => 'PPLG']));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'kode_jurusan' => 'PPLG',
                'nama_jurusan' => 'Pengembangan Perangkat Lunak dan Gim',
            ],
        ]);
    }

    /**
     * Test downstream application menerima data jurusan saat memanggil GET /api/v1/user
     */
    public function test_downstream_api_user_endpoint_includes_jurusan(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        $alumniUser = User::factory()->create([
            'name' => 'Alumni PPL SSO',
            'email' => 'alumni.ppl.sso@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        $app = Application::create([
            'name' => 'Aplikasi Downstream Tracer Study',
            'slug' => 'tracer-study',
            'client_id' => 'tracer_study_client',
            'client_secret' => 'tracer_study_secret',
            'base_url' => 'http://tracer.sch.id',
            'redirect_uri' => 'http://tracer.sch.id/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'bearer_token_'.Str::random(32);
        OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $alumniUser->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        $response = $this->withToken($tokenStr)->getJson(route('api.v1.user'));

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'alumni.ppl.sso@skansaba.sch.id',
            'classroom' => 'XII PPLG 1',
            'kode_jurusan' => 'PPLG',
            'nama_jurusan' => 'Pengembangan Perangkat Lunak dan Gim',
            'jurusan' => [
                'id' => $ppl->id,
                'kode_jurusan' => 'PPLG',
                'nama_jurusan' => 'Pengembangan Perangkat Lunak dan Gim',
            ],
        ]);
    }

    /**
     * Test downstream application dapat mengambil data alumni yang difilter per jurusan via GET /api/v1/alumni
     */
    public function test_downstream_api_alumni_endpoint_filterable_by_jurusan(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();
        $to = Jurusan::where('kode_jurusan', 'TO')->first();

        User::factory()->create([
            'name' => 'Alumni PPL 1',
            'email' => 'ppl1@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
        ]);

        User::factory()->create([
            'name' => 'Alumni TO 1',
            'email' => 'to1@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII TO 1',
            'jurusan_id' => $to->id,
        ]);

        $app = Application::create([
            'name' => 'Aplikasi Downstream BKK',
            'slug' => 'bkk-online',
            'client_id' => 'bkk_online_client',
            'client_secret' => 'bkk_online_secret',
            'base_url' => 'http://bkk.sch.id',
            'redirect_uri' => 'http://bkk.sch.id/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'bearer_token_bkk_'.Str::random(32);
        OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->admin->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        // Query filter jurusan PPLG
        $responsePpl = $this->withToken($tokenStr)->getJson(route('api.v1.alumni', ['jurusan' => 'PPLG']));
        $responsePpl->assertStatus(200);
        $this->assertEquals(1, count($responsePpl->json('data')));
        $this->assertEquals('Alumni PPL 1', $responsePpl->json('data.0.name'));
        $this->assertEquals('PPLG', $responsePpl->json('data.0.kode_jurusan'));

        // Query filter jurusan TO
        $responseTo = $this->withToken($tokenStr)->getJson(route('api.v1.alumni', ['jurusan' => 'TO']));
        $responseTo->assertStatus(200);
        $this->assertEquals(1, count($responseTo->json('data')));
        $this->assertEquals('Alumni TO 1', $responseTo->json('data.0.name'));
        $this->assertEquals('TO', $responseTo->json('data.0.kode_jurusan'));
    }

    /**
     * Test User model memiliki accessor tahun_masuk dan tahun_lulus
     */
    public function test_user_model_has_tahun_masuk_and_tahun_lulus_attributes(): void
    {
        $user = User::factory()->create([
            'role' => 'alumni',
            'created_at' => '2023-07-15 08:30:00',
            'updated_at' => '2026-05-20 11:45:00',
        ]);

        $this->assertEquals(2023, $user->tahun_masuk);
        $this->assertEquals(2026, $user->tahun_lulus);
        $this->assertNotEmpty($user->tahun_masuk_tanggal);
        $this->assertNotEmpty($user->tahun_lulus_tanggal);

        // Pastikan append ke array/json
        $array = $user->toArray();
        $this->assertArrayHasKey('tahun_masuk', $array);
        $this->assertArrayHasKey('tahun_lulus', $array);
        $this->assertEquals(2023, $array['tahun_masuk']);
        $this->assertEquals(2026, $array['tahun_lulus']);
    }

    /**
     * Test endpoint GET /api/v1/alumni mengembalikan tahun_masuk dan tahun_lulus untuk aplikasi downstream
     */
    public function test_downstream_api_alumni_endpoint_returns_tahun_masuk_and_tahun_lulus(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        $alumni = User::factory()->create([
            'name' => 'Budi Santoso Alumni',
            'email' => 'budialumni@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 2',
            'jurusan_id' => $ppl->id,
            'created_at' => '2022-07-10 09:00:00',
            'updated_at' => '2025-06-15 10:00:00',
        ]);

        $app = Application::create([
            'name' => 'Aplikasi Tracer Study',
            'slug' => 'tracer-study',
            'client_id' => 'tracer_study_client',
            'client_secret' => 'tracer_study_secret',
            'base_url' => 'http://tracer.sch.id',
            'redirect_uri' => 'http://tracer.sch.id/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'bearer_token_tracer_'.Str::random(32);
        OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->admin->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        $response = $this->withToken($tokenStr)->getJson(route('api.v1.alumni', ['search' => 'Budi Santoso Alumni']));
        $response->assertStatus(200);

        $data = $response->json('data.0');
        $this->assertEquals('Budi Santoso Alumni', $data['name']);
        $this->assertEquals(2022, $data['tahun_masuk']);
        $this->assertEquals(2025, $data['tahun_lulus']);
        $this->assertNotNull($data['created_at']);
        $this->assertNotNull($data['updated_at']);
    }

    /**
     * Test downstream API GET /api/v1/alumni dapat difilter berdasarkan tahun_masuk dan tahun_lulus
     */
    public function test_downstream_api_alumni_endpoint_filterable_by_tahun_masuk_and_lulus(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'Alumni Angkatan 2021',
            'email' => 'alumni2021@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
            'created_at' => '2021-07-10 09:00:00',
            'updated_at' => '2024-06-15 10:00:00',
        ]);

        User::factory()->create([
            'name' => 'Alumni Angkatan 2023',
            'email' => 'alumni2023@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 2',
            'jurusan_id' => $ppl->id,
            'created_at' => '2023-07-10 09:00:00',
            'updated_at' => '2026-06-15 10:00:00',
        ]);

        $app = Application::create([
            'name' => 'Portal Alumni External',
            'slug' => 'portal-alumni-ext',
            'client_id' => 'portal_alumni_ext_client',
            'client_secret' => 'portal_alumni_ext_secret',
            'base_url' => 'http://portalalumni.sch.id',
            'redirect_uri' => 'http://portalalumni.sch.id/callback',
            'status' => 'active',
        ]);

        $tokenStr = 'bearer_token_portal_'.Str::random(32);
        OAuthAccessToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->admin->id,
            'application_id' => $app->id,
            'token' => $tokenStr,
            'scopes' => 'openid profile email',
            'expires_at' => now()->addHours(24),
            'revoked' => false,
        ]);

        // Filter tahun masuk 2021
        $res2021 = $this->withToken($tokenStr)->getJson(route('api.v1.alumni', ['tahun_masuk' => 2021]));
        $res2021->assertStatus(200);
        $this->assertEquals(1, count($res2021->json('data')));
        $this->assertEquals('Alumni Angkatan 2021', $res2021->json('data.0.name'));

        // Filter tahun lulus 2026
        $res2026 = $this->withToken($tokenStr)->getJson(route('api.v1.alumni', ['tahun_lulus' => 2026]));
        $res2026->assertStatus(200);
        $this->assertEquals(1, count($res2026->json('data')));
        $this->assertEquals('Alumni Angkatan 2023', $res2026->json('data.0.name'));
    }

    /**
     * Test admin halaman pengelompokan alumni menampilkan kolom Tahun Masuk & Lulus
     */
    public function test_admin_jurusan_view_displays_tahun_masuk_and_lulus_columns(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'Siswa Lulus 2026',
            'email' => 'lulus2026@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
            'created_at' => '2023-07-01 08:00:00',
            'updated_at' => '2026-06-01 09:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jurusan.index'));
        $response->assertStatus(200);
        $response->assertSee('Tahun Masuk');
        $response->assertSee('Tahun Lulus');
        $response->assertSee('2023');
        $response->assertSee('2026');
    }

    /**
     * Test aplikasi downstream dapat mengakses daftar alumni secara publik tanpa token
     */
    public function test_downstream_apps_can_access_alumni_endpoints_publicly(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'Alumni Terbuka 1',
            'email' => 'terbuka1@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
            'created_at' => '2023-09-13 00:31:57',
            'updated_at' => '2025-05-05 03:44:01',
        ]);

        $response = $this->getJson(route('api.v1.alumni'));
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    /**
     * Test aplikasi downstream dapat mengambil detail alumni tunggal berdasarkan NIS
     */
    public function test_downstream_apps_can_query_single_alumni_detail_by_nis(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'MUHAMMAD ZAKARIA',
            'email' => 'zakaria@skansaba.sch.id',
            'external_id' => '3924',
            'username' => '3924',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
            'created_at' => '2023-09-13 00:31:57',
            'updated_at' => '2025-05-05 03:44:01',
        ]);

        $response = $this->getJson(route('api.v1.alumni_detail', ['identifier' => '3924']));
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'nis' => '3924',
                'name' => 'MUHAMMAD ZAKARIA',
                'tahun_masuk' => 2023,
                'tahun_lulus' => 2025,
                'classroom' => 'XII PPLG 1',
                'kode_jurusan' => 'PPLG',
            ],
        ]);
    }

    /**
     * Test aplikasi downstream dapat mengambil alumni per jurusan via /jurusans/{kode}/alumni
     */
    public function test_downstream_apps_can_access_alumni_by_jurusan_route(): void
    {
        $ppl = Jurusan::where('kode_jurusan', 'PPLG')->first();

        User::factory()->create([
            'name' => 'Alumni PPL Jurusan Route',
            'email' => 'pplroute@skansaba.sch.id',
            'role' => 'alumni',
            'classroom' => 'XII PPLG 1',
            'jurusan_id' => $ppl->id,
            'created_at' => '2023-09-13 00:31:57',
            'updated_at' => '2025-05-05 03:44:01',
        ]);

        $response = $this->getJson(route('api.v1.jurusan_alumni', ['kode' => 'PPLG']));
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertEquals('PPLG', $response->json('data.0.kode_jurusan'));
    }
}

