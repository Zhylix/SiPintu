<?php

namespace Database\Seeders;

use App\Jobs\SyncSijunaStudentsJob;
use App\Jobs\SyncSijunaTeachersJob;
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
        $rolesData = ['admin', 'teacher', 'dudi', 'student', 'alumni'];

        $rolesMap = [];
        foreach ($rolesData as $roleName) {
            $rolesMap[$roleName] = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['slug' => Str::slug($roleName)]
            );
            if (empty($rolesMap[$roleName]->slug)) {
                $rolesMap[$roleName]->update(['slug' => Str::slug($roleName)]);
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

        // 3. Admin User Initialization & Synchronization from .env
        $adminConfig = config('auth.admin', [
            'name' => env('ADMIN_NAME', 'Administrator SiPintu'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'email' => env('ADMIN_EMAIL', 'admin@smkn1bangsri.sch.id'),
            'password' => env('ADMIN_PASSWORD', 'password'),
        ]);

        $adminUser = User::where('role', 'admin')
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->orWhere('email', 'admin@gateway.sekolah.id')
            ->orWhere('email', $adminConfig['email'])
            ->first();

        if (! $adminUser) {
            $adminUser = User::create([
                'name' => $adminConfig['name'],
                'username' => $adminConfig['username'],
                'email' => $adminConfig['email'],
                'password' => Hash::make($adminConfig['password']),
                'role' => 'admin',
                'status' => 'active',
            ]);
            $adminUser->syncRoles(['admin']);
        } elseif ($adminUser->email === 'admin@gateway.sekolah.id') {
            // Migrasikan akun dummy lama ke kredensial resmi dari .env
            $adminUser->update([
                'name' => $adminConfig['name'],
                'username' => $adminConfig['username'],
                'email' => $adminConfig['email'],
                'password' => Hash::make($adminConfig['password']),
                'role' => 'admin',
                'status' => 'active',
            ]);
            $adminUser->syncRoles(['admin']);
        }

        // 4. Auto Sync Real Data from SIJUNA API (Siswa, Alumni, Guru)
        try {
            SyncSijunaStudentsJob::dispatchSync();
        } catch (\Throwable $e) {
            // Ignore if queue or network issue
        }

        try {
            SyncSijunaTeachersJob::dispatchSync();
        } catch (\Throwable $e) {
            // Ignore if queue or network issue
        }
    }
}
