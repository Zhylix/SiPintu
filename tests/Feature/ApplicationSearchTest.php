<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'], ['slug' => 'student']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web'], ['slug' => 'teacher']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['slug' => 'admin']);
    }

    protected function createStudent(): User
    {
        $user = User::factory()->create([
            'name' => 'Siswa Search Test',
            'username' => 'siswa_search_' . rand(1000, 9999),
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
        ]);
        $user->syncRoles(['student']);
        return $user;
    }

    protected function createTeacher(): User
    {
        $user = User::factory()->create([
            'name' => 'Guru Search Test',
            'username' => 'guru_search_' . rand(1000, 9999),
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'status' => 'active',
        ]);
        $user->syncRoles(['teacher']);
        return $user;
    }

    public function test_student_can_search_applications_by_name_and_description(): void
    {
        $student = $this->createStudent();
        $studentRole = Role::where('name', 'student')->first();

        $category = ApplicationCategory::firstOrCreate(
            ['slug' => 'ujian-evaluasi'],
            ['name' => 'Ujian & Evaluasi']
        );

        $cbtApp = Application::create([
            'name' => 'CBT Smkn1Bangsri',
            'slug' => 'cbt-smkn1bangsri-' . Str::random(5),
            'description' => 'Aplikasi pelaksanaan ujian sekolah online',
            'category_id' => $category->id,
            'client_id' => 'cbt_client_' . Str::random(8),
            'client_secret' => 'secret123',
            'base_url' => 'https://cbt.example.com',
            'redirect_uri' => 'https://cbt.example.com/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);
        $cbtApp->roles()->sync([$studentRole->id]);

        $raporApp = Application::create([
            'name' => 'E-Rapor Digital',
            'slug' => 'e-rapor-' . Str::random(5),
            'description' => 'Portal pelaporan hasil belajar siswa semester',
            'client_id' => 'rapor_client_' . Str::random(8),
            'client_secret' => 'secret123',
            'base_url' => 'https://rapor.example.com',
            'redirect_uri' => 'https://rapor.example.com/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);
        $raporApp->roles()->sync([$studentRole->id]);

        // 1. Visit apps catalog without search -> Sees both and dropdown elements
        $response = $this->actingAs($student)->get(route('student.apps'));
        $response->assertStatus(200);
        $response->assertSee('CBT Smkn1Bangsri');
        $response->assertSee('E-Rapor Digital');
        $response->assertSee('Cari aplikasi, kategori, deskripsi...');
        $response->assertSee('Kategori Aplikasi');
        $response->assertSee('searchDropdownOpen');
        $response->assertSee('Pilih Kategori Aplikasi');
        $response->assertViewHas('applications', function ($apps) use ($cbtApp, $raporApp) {
            return $apps->contains('id', $cbtApp->id) && $apps->contains('id', $raporApp->id);
        });

        // 2. Search by name 'CBT' -> Only sees CBT in applications collection
        $responseSearch = $this->actingAs($student)->get(route('student.apps', ['search' => 'CBT']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('CBT Smkn1Bangsri');
        $responseSearch->assertViewHas('applications', function ($apps) use ($cbtApp, $raporApp) {
            return $apps->contains('id', $cbtApp->id) && ! $apps->contains('id', $raporApp->id);
        });

        // 3. Search by description keyword 'semester' -> Only sees E-Rapor in applications collection
        $responseDesc = $this->actingAs($student)->get(route('student.apps', ['search' => 'semester']));
        $responseDesc->assertStatus(200);
        $responseDesc->assertSee('E-Rapor Digital');
        $responseDesc->assertViewHas('applications', function ($apps) use ($cbtApp, $raporApp) {
            return $apps->contains('id', $raporApp->id) && ! $apps->contains('id', $cbtApp->id);
        });

        // 4. Search by category filter
        $responseCat = $this->actingAs($student)->get(route('student.apps', ['category' => $category->id]));
        $responseCat->assertStatus(200);
        $responseCat->assertSee('CBT Smkn1Bangsri');
        $responseCat->assertViewHas('applications', function ($apps) use ($cbtApp, $raporApp) {
            return $apps->contains('id', $cbtApp->id) && ! $apps->contains('id', $raporApp->id);
        });

        // Clean up
        $cbtApp->delete();
        $raporApp->delete();
        $student->delete();
    }

    public function test_student_does_not_see_teacher_only_applications_in_search(): void
    {
        $student = $this->createStudent();
        $teacherRole = Role::where('name', 'teacher')->first();

        $teacherOnlyApp = Application::create([
            'name' => 'Jurnal Guru Eksklusif',
            'slug' => 'jurnal-guru-' . Str::random(5),
            'description' => 'Aplikasi khusus pengisian jurnal mengajar guru',
            'client_id' => 'guru_client_' . Str::random(8),
            'client_secret' => 'secret123',
            'base_url' => 'https://guru.example.com',
            'redirect_uri' => 'https://guru.example.com/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);
        $teacherOnlyApp->roles()->sync([$teacherRole->id]);

        $response = $this->actingAs($student)->get(route('student.apps', ['search' => 'Jurnal']));
        $response->assertStatus(200);
        $response->assertViewHas('applications', function ($apps) use ($teacherOnlyApp) {
            return ! $apps->contains('id', $teacherOnlyApp->id);
        });

        // Clean up
        $teacherOnlyApp->delete();
        $student->delete();
    }

    public function test_global_search_modal_and_trigger_are_rendered_in_layout(): void
    {
        $student = $this->createStudent();
        $studentRole = Role::where('name', 'student')->first();

        $app = Application::create([
            'name' => 'Perpustakaan Digital',
            'slug' => 'perpus-' . Str::random(5),
            'description' => 'Akses koleksi buku dan literasi online',
            'client_id' => 'perpus_client_' . Str::random(8),
            'client_secret' => 'secret123',
            'base_url' => 'https://perpus.example.com',
            'redirect_uri' => 'https://perpus.example.com/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);
        $app->roles()->sync([$studentRole->id]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response->assertStatus(200);
        // Verify trigger button with shortcut Ctrl K
        $response->assertSee('Cari aplikasi...');
        $response->assertSee('Ctrl K');
        // Verify global search modal markup
        $response->assertSee('open-global-search');
        $response->assertSee('Perpustakaan Digital');

        // Clean up
        $app->delete();
        $student->delete();
    }

    public function test_teacher_dashboard_search_and_stats(): void
    {
        $teacher = $this->createTeacher();
        $teacherRole = Role::where('name', 'teacher')->first();

        $teacherApp = Application::create([
            'name' => 'Presensi Guru Bangsri',
            'slug' => 'presensi-guru-' . Str::random(5),
            'description' => 'Presensi kehadiran mengajar guru',
            'client_id' => 'presensi_guru_' . Str::random(8),
            'client_secret' => 'secret123',
            'base_url' => 'https://presensi.example.com',
            'redirect_uri' => 'https://presensi.example.com/callback',
            'scopes' => 'openid profile',
            'status' => 'active',
        ]);
        $teacherApp->roles()->sync([$teacherRole->id]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard', ['search' => 'Presensi']));
        $response->assertStatus(200);
        $response->assertViewHas('applications', function ($apps) use ($teacherApp) {
            return $apps->contains('id', $teacherApp->id);
        });

        $responseEmpty = $this->actingAs($teacher)->get(route('teacher.dashboard', ['search' => 'NonExistentApplicationQuery']));
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertViewHas('applications', function ($apps) {
            return $apps->isEmpty();
        });

        // Clean up
        $teacherApp->delete();
        $teacher->delete();
    }
}
