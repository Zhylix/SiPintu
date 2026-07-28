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

        // Default Guru User
        $guruUser = User::firstOrCreate(
            ['email' => 'guru@gateway.sekolah.id'],
            [
                'name' => 'Bpk. Ahmad Fauzi, M.Kom',
                'username' => 'guru',
                'password' => Hash::make('password'),
                'user_type' => 'teacher',
                'status' => 'active',
            ]
        );
        $guruUser->roles()->sync([$rolesMap['teacher']->id]);

        // Default DUDI User
        $dudiUser = User::firstOrCreate(
            ['email' => 'dudi@gateway.sekolah.id'],
            [
                'name' => 'PT Telkom Indonesia (Mitra DUDI)',
                'username' => 'dudi',
                'password' => Hash::make('password'),
                'user_type' => 'dudi',
                'status' => 'active',
            ]
        );
        $dudiUser->roles()->sync([$rolesMap['dudi']->id]);

        // 4. Auto Sync Real Students from SIJUNA API
        try {
            \App\Jobs\SyncSijunaStudentsJob::dispatchSync();
        } catch (\Throwable $e) {
            // Ignore if offline
        }
    }
}
