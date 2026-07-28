<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions', 'applications', 'users'])->get();
        $permissions = Permission::all();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $role->permissions()->sync($permissionIds);

        AuditLogger::log('admin_update_role_permissions', [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'permissions_count' => count($permissionIds),
        ]);

        return back()->with('success', "Izin hak akses untuk role {$role->name} berhasil diperbarui.");
    }
}
