<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('type')) {
            $query->where('role', $request->type);
        } elseif ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $userRole = $request->input('role', $request->input('user_type'));
        $request->merge(['role' => $userRole]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['teacher', 'dudi', 'admin', 'student'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'username.unique' => 'Username ini sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ]);

        // Assign corresponding role using Spatie Permission
        $role = Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->assignRole($role);

        AuditLogger::log('admin_create_user', [
            'created_user_id' => $user->id,
            'role' => $user->role,
            'email' => $user->email,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->role} ({$user->name}) berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $userRole = $request->input('role', $request->input('user_type'));
        $request->merge(['role' => $userRole]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['teacher', 'dudi', 'admin', 'student'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Sync role using Spatie Permission
        $role = Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->syncRoles([$role]);

        AuditLogger::log('admin_update_user', [
            'updated_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya administrator sistem.');
        }

        $userName = $user->name;
        AuditLogger::log('admin_delete_user', [
            'deleted_user_id' => $user->id,
            'email' => $user->email,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$userName} telah dihapus.");
    }
}
