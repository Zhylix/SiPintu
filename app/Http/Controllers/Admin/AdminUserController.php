<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PasswordSyncService;
use App\Services\UserImportService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'jurusan']);

        // Role Filter (ignore 'all' or empty)
        $role = $request->input('role', $request->input('type'));
        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        // Jurusan Filter (PPL, TO, AKL, PM, MPLB)
        if ($request->filled('jurusan') && $request->jurusan !== 'all') {
            $jurusanKode = $request->jurusan;
            $query->whereHas('jurusan', function ($q) use ($jurusanKode) {
                $q->where('kode_jurusan', $jurusanKode);
            });
        }

        // Account Status Filter (ignore 'all' or empty)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Phone Status Filter (ignore 'all' or empty)
        if ($request->filled('phone_status') && $request->phone_status !== 'all') {
            if ($request->phone_status === 'with_phone') {
                $query->whereNotNull('phone')->where('phone', '!=', '')->where('phone', '!=', '0');
            } elseif ($request->phone_status === 'without_phone') {
                $query->where(function ($q) {
                    $q->whereNull('phone')->orWhere('phone', '')->orWhere('phone', '0');
                });
            }
        }

        // Keyword Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('classroom', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("COALESCE(NULLIF(external_id, ''), NULLIF(username, ''), name) ASC")
            ->paginate(15)
            ->withQueryString();

        $roles = Role::all();
        $jurusans = Jurusan::orderByRaw("FIELD(kode_jurusan, 'PPLG', 'TO', 'AKL', 'PM', 'MPLB')")->get();

        return view('admin.users.index', compact('users', 'roles', 'jurusans'));
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
            'external_id' => ['nullable', 'string', 'max:100', 'unique:users,external_id'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['teacher', 'dudi', 'student', 'alumni'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'username.unique' => 'Username ini sudah digunakan.',
            'external_id.unique' => 'NIS / NIP / ID Eksternal ini sudah digunakan.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'external_id' => $validated['external_id'] ?? null,
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

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('admin.users.edit', $user);
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
            'external_id' => ['nullable', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['teacher', 'dudi', 'admin', 'student', 'alumni'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:8'],
            'must_change_password' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'username.unique' => 'Username ini sudah digunakan.',
            'external_id.unique' => 'NIS / NIP / ID Eksternal ini sudah digunakan.',
        ]);

        if (auth()->id() === $user->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri dari Administrator.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'external_id' => $validated['external_id'] ?? null,
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ];

        $passwordChanged = false;
        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
            $passwordChanged = true;
        }

        if ($request->has('must_change_password')) {
            $updateData['must_change_password'] = $request->boolean('must_change_password');
        }

        $user->update($updateData);

        // Sync role using Spatie Permission
        $role = Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->syncRoles([$role]);

        if ($passwordChanged) {
            app(PasswordSyncService::class)->broadcastPasswordChange($user);
        }

        AuditLogger::log('admin_update_user', [
            'updated_user_id' => $user->id,
            'email' => $user->email,
            'password_synced' => $passwordChanged,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui dan disinkronkan ke seluruh aplikasi downstream.");
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

    public function updatePhone(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $oldPhone = $user->phone;
        $newPhone = ! empty($validated['phone']) ? trim($validated['phone']) : null;

        $user->update(['phone' => $newPhone]);

        AuditLogger::log('admin_update_user_phone', [
            'updated_user_id' => $user->id,
            'user_name' => $user->name,
            'old_phone' => $oldPhone,
            'new_phone' => $newPhone,
        ]);

        $phoneDisplay = $newPhone ?: '(kosong/dihapus)';

        return back()->with('success', "Nomor WhatsApp untuk {$user->name} berhasil diperbarui menjadi {$phoneDisplay}.");
    }

    /**
     * Reset user password by Administrator (Quick Reset, Custom, or Randomly Generated).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'reset_type' => ['required', 'string', 'in:default,custom,random'],
            'password' => ['nullable', 'required_if:reset_type,custom', 'string', 'min:8'],
            'must_change_password' => ['nullable', 'boolean'],
            'send_whatsapp' => ['nullable', 'boolean'],
        ], [
            'password.required_if' => 'Kata sandi kustom wajib diisi.',
            'password.min' => 'Kata sandi kustom minimal 8 karakter.',
        ]);

        $resetType = $validated['reset_type'];

        // Determine password
        if ($resetType === 'custom') {
            $newPassword = $validated['password'];
        } elseif ($resetType === 'random') {
            // Generates memorable yet secure password, e.g. Pintu#5921
            $newPassword = 'Pintu#' . random_int(1000, 9999);
        } else {
            // Default system password
            $newPassword = 'password';
        }

        $mustChange = $request->boolean('must_change_password', true);

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => $mustChange,
        ]);

        // Broadcast to downstream SSO client applications
        app(PasswordSyncService::class)->broadcastPasswordChange($user);

        // Audit log
        AuditLogger::log('admin_reset_user_password', [
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_user_role' => $user->role,
            'target_identifier' => $user->external_id ?: ($user->username ?: $user->email),
            'reset_type' => $resetType,
            'must_change_password' => $mustChange,
            'performed_by' => auth()->id(),
        ], $user->id);

        // Optional WhatsApp notification if user has registered phone
        $waSent = false;
        $waError = null;
        if ($request->boolean('send_whatsapp') && ! empty($user->phone)) {
            $whatsAppService = app(WhatsAppService::class);
            $cleanPhone = $whatsAppService->formatPhoneNumber($user->phone);

            if ($cleanPhone) {
                $identifier = $user->external_id ?: ($user->username ?: $user->email);
                $msg = "*PEMBERITAHUAN RESET KATA SANDI SIPINTU*\n\n".
                    "Halo, *{$user->name}*.\n\n".
                    "Kata sandi akun SiPintu Anda telah berhasil direset oleh Administrator sekolah.\n\n".
                    "*Identifier Akun:* {$identifier}\n".
                    "*Kata Sandi Baru:* `{$newPassword}`\n\n";

                if ($mustChange) {
                    $msg .= "[PENTING] Demi keamanan akun, Anda akan diminta untuk mengganti kata sandi ini menjadi kata sandi pribadi saat pertama kali login.\n\n";
                }

                $msg .= "Silakan login melalui: " . config('app.url') . "/login\n\n".
                    "_SiPintu Identity Gateway - SMKN 1 Bangsri_";

                $res = $whatsAppService->sendMessage($cleanPhone, $msg);
                $waSent = ($res['success'] ?? false);
                if (! $waSent) {
                    $waError = $res['error'] ?? 'Layanan WhatsApp bot tidak merespons.';
                }
            } else {
                $waError = 'Nomor WhatsApp tidak valid.';
            }
        }

        return back()->with('reset_password_success', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $user->role,
            'identifier' => $user->external_id ?: ($user->username ?: $user->email),
            'password' => $newPassword,
            'must_change' => $mustChange,
            'wa_sent' => $waSent,
            'wa_error' => $waError,
        ]);
    }

    /**
     * Download template CSV file for bulk user import.
     */
    public function downloadTemplate(UserImportService $importService): Response
    {
        $csvContent = $importService->getCsvTemplate();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_user_sipintu.csv"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Handle bulk user import from CSV file.
     */
    public function import(Request $request, UserImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:10240'], // Max 10MB
            'update_existing' => ['nullable', 'boolean'],
            'force_change_password' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'Pilih file CSV yang akan diunggah.',
            'file.extensions' => 'Format file harus berupa CSV (.csv atau .txt).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        $updateExisting = $request->boolean('update_existing', true);
        $forcePasswordChange = $request->boolean('force_change_password', true);

        $result = $importService->importFromCsv($request->file('file'), $updateExisting, $forcePasswordChange);

        if (! $result['success']) {
            return back()->with('error', $result['message'])->with('import_errors', $result['errors'] ?? []);
        }

        if (! empty($result['errors'])) {
            return back()
                ->with('success', $result['message'])
                ->with('import_warnings', $result['errors']);
        }

        return back()->with('success', $result['message']);
    }
}
