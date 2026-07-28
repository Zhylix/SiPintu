<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles Definition
        $rolesData = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Administrator Utama Gateway Identity & SSO Provider.',
            ],
            [
                'name' => 'Guru / Pendidik',
                'slug' => 'teacher',
                'description' => 'Tenaga pendidik dan pengajar di sekolah.',
            ],
            [
                'name' => 'Mitra DUDI / Industri',
                'slug' => 'dudi',
                'description' => 'Dunia Usaha dan Dunia Industri (Mitra PKL).',
            ],
            [
                'name' => 'Siswa (SIJUNA)',
                'slug' => 'student',
                'description' => 'Peserta didik aktif yang bersumber dari API SIJUNA.',
            ],
        ];

        $rolesMap = [];
        foreach ($rolesData as $r) {
            $rolesMap[$r['slug']] = Role::firstOrCreate(['slug' => $r['slug']], $r);
        }

        // 2. Permissions Definition
        $permissionsData = [
            ['name' => 'Manage Users', 'slug' => 'manage-users', 'description' => 'Membuat, mengubah, dan menghapus pengguna.'],
            ['name' => 'Manage Applications', 'slug' => 'manage-applications', 'description' => 'Mengelola registry aplikasi eksternal.'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles', 'description' => 'Mengatur role dan hak akses permission.'],
            ['name' => 'Sync SIJUNA API', 'slug' => 'sync-sijuna', 'description' => 'Menjalankan sinkronisasi data siswa dari SIJUNA.'],
            ['name' => 'View Audit Logs', 'slug' => 'view-audit-logs', 'description' => 'Melihat catatan audit log aktivitas.'],
            ['name' => 'Access External Apps', 'slug' => 'access-external-apps', 'description' => 'Izin melakukan SSO login ke aplikasi eksternal.'],
        ];

        $permMap = [];
        foreach ($permissionsData as $p) {
            $permMap[$p['slug']] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Assign all permissions to Admin
        $rolesMap['admin']->permissions()->sync(array_column($permMap, 'id'));
        
        // Assign Access External Apps to all roles
        foreach ($rolesMap as $role) {
            if ($role->slug !== 'admin') {
                $role->permissions()->syncWithoutDetaching([$permMap['access-external-apps']->id]);
            }
        }

        // 3. Default Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gateway.sekolah.id'],
            [
                'name' => 'Administrator Gateway',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'status' => 'active',
            ]
        );
        $adminUser->roles()->sync([$rolesMap['admin']->id]);

        // Default Teacher User
        $teacherUser = User::firstOrCreate(
            ['email' => 'guru@sekolah.id'],
            [
                'name' => 'Drs. Supriyadi, M.Pd',
                'username' => 'guru_supriyadi',
                'password' => Hash::make('password'),
                'user_type' => 'teacher',
                'status' => 'active',
            ]
        );
        $teacherUser->roles()->sync([$rolesMap['teacher']->id]);

        // Default DUDI User
        $dudiUser = User::firstOrCreate(
            ['email' => 'admin@majujaya.co.id'],
            [
                'name' => 'PT Maju Jaya Industri',
                'username' => 'dudi_majujaya',
                'password' => Hash::make('password'),
                'user_type' => 'dudi',
                'status' => 'active',
            ]
        );
        $dudiUser->roles()->sync([$rolesMap['dudi']->id]);

        // 4. Sample Students (Simulating SIJUNA API cached / synced data)
        $students = [
            ['external_id' => 'SIJ-STUDENT-001', 'name' => 'Ahmad Rizky Pratama', 'email' => 'ahmad.rizky@siswa.sekolah.id'],
            ['external_id' => 'SIJ-STUDENT-002', 'name' => 'Siti Nurhaliza', 'email' => 'siti.nurhaliza@siswa.sekolah.id'],
            ['external_id' => 'SIJ-STUDENT-003', 'name' => 'Budi Santoso', 'email' => 'budi.santoso@siswa.sekolah.id'],
            ['external_id' => 'SIJ-STUDENT-004', 'name' => 'Dewi Anggraini', 'email' => 'dewi.anggraini@siswa.sekolah.id'],
            ['external_id' => 'SIJ-STUDENT-005', 'name' => 'Eko Prasetyo', 'email' => 'eko.prasetyo@siswa.sekolah.id'],
        ];

        foreach ($students as $st) {
            $studentUser = User::firstOrCreate(
                ['external_id' => $st['external_id']],
                [
                    'name' => $st['name'],
                    'email' => $st['email'],
                    'user_type' => 'student',
                    'status' => 'active',
                    'password' => Hash::make(Str::random(32)),
                ]
            );
            $studentUser->roles()->sync([$rolesMap['student']->id]);
        }

        // 5. External Applications Registration
        $baseUrl = config('app.url', 'http://127.0.0.1:8000');

        $applicationsData = [
            [
                'name' => 'Aplikasi PKL Eksternal',
                'slug' => 'aplikasi-pkl',
                'description' => 'Aplikasi Praktik Kerja Lapangan untuk Siswa dan Guru Pembimbing.',
                'base_url' => "{$baseUrl}/demo/pkl",
                'icon' => 'briefcase',
                'client_id' => 'app_pkl_client',
                'client_secret' => Hash::make('secret_pkl_12345'),
                'redirect_uri' => "{$baseUrl}/demo/pkl/callback",
                'logout_uri' => "{$baseUrl}/demo/pkl/logout",
                'scopes' => 'openid profile email',
                'status' => 'active',
                'health_check_url' => "{$baseUrl}/demo/health",
                'roles' => [$rolesMap['student']->id, $rolesMap['teacher']->id, $rolesMap['dudi']->id, $rolesMap['admin']->id],
            ],
            [
                'name' => 'Aplikasi Akademik',
                'slug' => 'aplikasi-akademik',
                'description' => 'Portal Nilai dan Raport Akademik Terpadu.',
                'base_url' => "{$baseUrl}/demo/akademik",
                'icon' => 'academic-cap',
                'client_id' => 'app_akademik_client',
                'client_secret' => Hash::make('secret_akademik_12345'),
                'redirect_uri' => "{$baseUrl}/demo/akademik/callback",
                'logout_uri' => "{$baseUrl}/demo/akademik/logout",
                'scopes' => 'openid profile email',
                'status' => 'active',
                'health_check_url' => "{$baseUrl}/demo/health",
                'roles' => [$rolesMap['student']->id, $rolesMap['teacher']->id, $rolesMap['admin']->id],
            ],
            [
                'name' => 'Aplikasi Presensi Digital',
                'slug' => 'aplikasi-presensi',
                'description' => 'Aplikasi Kehadiran & Absensi Harian Sekolah.',
                'base_url' => "{$baseUrl}/demo/presensi",
                'icon' => 'clock',
                'client_id' => 'app_presensi_client',
                'client_secret' => Hash::make('secret_presensi_12345'),
                'redirect_uri' => "{$baseUrl}/demo/presensi/callback",
                'logout_uri' => "{$baseUrl}/demo/presensi/logout",
                'scopes' => 'openid profile email',
                'status' => 'active',
                'health_check_url' => "{$baseUrl}/demo/health",
                'roles' => [$rolesMap['student']->id, $rolesMap['teacher']->id, $rolesMap['admin']->id],
            ],
        ];

        foreach ($applicationsData as $appData) {
            $allowedRoles = $appData['roles'];
            unset($appData['roles']);

            $app = Application::updateOrCreate(
                ['client_id' => $appData['client_id']],
                $appData
            );

            $app->roles()->sync($allowedRoles);
        }
    }
}
