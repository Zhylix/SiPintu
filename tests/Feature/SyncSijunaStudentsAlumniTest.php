<?php

namespace Tests\Feature;

use App\Jobs\SyncSijunaStudentsJob;
use App\Models\Role;
use App\Models\User;
use App\Services\SijunaApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncSijunaStudentsAlumniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'], ['slug' => 'student']);
        Role::firstOrCreate(['name' => 'alumni', 'guard_name' => 'web'], ['slug' => 'alumni']);
    }

    public function test_students_with_graduated_true_are_assigned_alumni_role(): void
    {
        $mockSijuna = $this->createMock(SijunaApiService::class);
        $mockSijuna->method('getStudents')->willReturn([
            [
                'id' => '1001',
                'nis' => '9901',
                'nama' => 'Budi Santoso (Lulus)',
                'classroom' => 'XII PPLG 1', // even if classroom is filled
                'email' => 'budi9901@siswa.sekolah.id',
                'graduated' => true,
            ],
            [
                'id' => '1002',
                'nis' => '9902',
                'nama' => 'Rina Permata (Aktif)',
                'classroom' => 'XI RPL 2',
                'email' => 'rina9902@siswa.sekolah.id',
                'graduated' => false,
            ],
            [
                'id' => '1003',
                'nis' => '9903',
                'nama' => 'Fajar Pratama (Legacy Alumni)',
                'classroom' => null, // legacy without graduated key
                'email' => 'fajar9903@siswa.sekolah.id',
            ],
            [
                'id' => '1004',
                'nis' => '9904',
                'nama' => 'Dewi Lestari (Graduated String 1)',
                'classroom' => 'XII AKL 1',
                'email' => 'dewi9904@siswa.sekolah.id',
                'graduated' => '1',
            ],
        ]);

        $job = new SyncSijunaStudentsJob();
        $job->handle($mockSijuna);

        // 1. Check Budi (graduated = true) -> Alumni
        $budi = User::where('external_id', '9901')->first();
        $this->assertNotNull($budi);
        $this->assertEquals('alumni', $budi->role);
        $this->assertTrue($budi->isAlumni());
        $this->assertTrue($budi->hasRole('alumni'));
        $this->assertFalse($budi->hasRole('student'));
        $this->assertEquals('XII PPLG 1', $budi->classroom);

        // 2. Check Rina (graduated = false) -> Student
        $rina = User::where('external_id', '9902')->first();
        $this->assertNotNull($rina);
        $this->assertEquals('student', $rina->role);
        $this->assertTrue($rina->isStudent());
        $this->assertTrue($rina->hasRole('student'));
        $this->assertFalse($rina->hasRole('alumni'));

        // 3. Check Fajar (no graduated key, classroom null) -> Alumni fallback
        $fajar = User::where('external_id', '9903')->first();
        $this->assertNotNull($fajar);
        $this->assertEquals('alumni', $fajar->role);
        $this->assertTrue($fajar->isAlumni());

        // 4. Check Dewi (graduated = '1') -> Alumni
        $dewi = User::where('external_id', '9904')->first();
        $this->assertNotNull($dewi);
        $this->assertEquals('alumni', $dewi->role);
        $this->assertTrue($dewi->isAlumni());
    }
}
