<?php

namespace Database\Seeders;

use App\Jobs\SyncSijunaStudentsJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles Definition
        $rolesData = ['admin', 'teacher', 'dudi', 'student', 'alumni'];

        $rolesMap = [];
        foreach ($rolesData as $roleName) {
            $rolesMap[$roleName] = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['slug' => \Illuminate\Support\Str::slug($roleName)]
            );
            if (empty($rolesMap[$roleName]->slug)) {
                $rolesMap[$roleName]->update(['slug' => \Illuminate\Support\Str::slug($roleName)]);
            }
        }

        // 2. Permissions Definition
        $permissionsData = [
            'manage-users',
            'manage-applications',
            'manage-roles',
            'sync-sijuna',
            'view-audit-logs',
            'access-external-apps',
        ];

        $permMap = [];
        foreach ($permissionsData as $permName) {
            $permMap[$permName] = Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        // Assign all permissions to Admin
        $rolesMap['admin']->syncPermissions(Permission::all());

        // Assign Access External Apps to all roles
        foreach ($rolesMap as $roleName => $role) {
            if ($roleName !== 'admin') {
                $role->givePermissionTo('access-external-apps');
            }
        }

        // 3. Default Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@gateway.sekolah.id'],
            [
                'name' => 'Administrator Gateway',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $adminUser->syncRoles(['admin']);

        // Default Guru User
        $guruUser = User::updateOrCreate(
            ['email' => 'guru@gateway.sekolah.id'],
            [
                'name' => 'Bpk. Ahmad Fauzi, M.Kom',
                'username' => 'guru',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'status' => 'active',
            ]
        );
        $guruUser->syncRoles(['teacher']);

        // Default DUDI User
        $dudiUser = User::updateOrCreate(
            ['email' => 'dudi@gateway.sekolah.id'],
            [
                'name' => 'PT Telkom Indonesia (Mitra DUDI)',
                'username' => 'dudi',
                'password' => Hash::make('password'),
                'role' => 'dudi',
                'status' => 'active',
            ]
        );
        $dudiUser->syncRoles(['dudi']);

        // Default Siswa User (Static Fallback)
        $siswaUser = User::updateOrCreate(
            ['username' => '4439'],
            [
                'name' => 'AFRILLIA FIFA ANANTA',
                'email' => '4439@smkn1bangsri.sch.id',
                'external_id' => '4439',
                'password' => Hash::make('password'),
                'role' => 'student',
                'status' => 'active',
            ]
        );
        $siswaUser->syncRoles(['student']);

        // 4. Auto Sync Real Students from SIJUNA API
        try {
            SyncSijunaStudentsJob::dispatchSync();
        } catch (\Throwable $e) {
            // Ignore if queue or network issue
        }
    }
}
