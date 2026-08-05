<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SijunaApiService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $accountType = $request->input('account_type', 'siswa');

        $identity = match ($accountType) {
            'guru' => trim((string) ($request->input('nip') ?: $request->input('identity', ''))),
            'dudi' => trim((string) ($request->input('kode_dudi') ?: $request->input('identity', ''))),
            default => trim((string) ($request->input('nis') ?: $request->input('identity', ''))),
        };

        $identityFieldName = match ($accountType) {
            'guru' => 'nip',
            'dudi' => 'kode_dudi',
            default => 'nis',
        };

        $request->validate([
            'password' => ['required', 'string'],
            'account_type' => ['nullable', 'string', 'in:siswa,guru,dudi,admin'],
        ], [
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (empty($identity)) {
            $label = match ($accountType) {
                'guru' => 'NIP atau Email Guru',
                'dudi' => 'Kode Mitra DUDI atau Email Perusahaan',
                default => 'NIS atau NISN Siswa',
            };

            return back()->withErrors([
                $identityFieldName => "Masukkan {$label} Anda.",
            ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
        }

        $password = $request->input('password');

        // 1. Try finding user in Gateway database by email, username, or external_id (SIJUNA)
        $user = User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('external_id', $identity)
            ->first();

        if ($user) {
            // 1. Direct login for Administrator with valid password regardless of active tab (Guru / DUDI / Siswa)
            if ($user->isAdmin()) {
                if (! Hash::check($password, $user->password)) {
                    AuditLogger::log('login_failed_password', ['identity' => $identity]);

                    return back()->withErrors([
                        'password' => 'Kata sandi yang Anda masukkan salah.',
                    ])->onlyInput('identity', 'account_type');
                }

                if ($user->status !== 'active') {
                    AuditLogger::log('login_failed_suspended', ['identity' => $identity]);

                    return back()->withErrors(['identity' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan.'])->onlyInput('identity', 'account_type');
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                AuditLogger::log('login_success_admin', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                ], $user->id);

                if (session()->has('oauth_return_to')) {
                    $returnTo = session()->pull('oauth_return_to');

                    return redirect()->to($returnTo);
                }

                return redirect()->route('admin.dashboard')->with('success', 'Berhasil login sebagai '.$user->name);
            }

            // 2. Strict Role Validation vs Selected Login Tab (Siswa, Guru, DUDI)
            $roleMismatch = false;
            if ($accountType === 'siswa' && ! $user->isStudent()) {
                $roleMismatch = true;
            } elseif ($accountType === 'guru' && ! $user->isTeacher()) {
                $roleMismatch = true;
            } elseif ($accountType === 'dudi' && ! $user->isDudi()) {
                $roleMismatch = true;
            }

            if ($roleMismatch) {
                $userRoleName = $user->getUserTypeName();
                $tabMap = [
                    'teacher' => 'Guru',
                    'dudi' => 'Mitra DUDI',
                    'student' => 'Siswa',
                ];
                $targetTab = $tabMap[$user->role] ?? $userRoleName;

                AuditLogger::log('login_failed_role_mismatch', [
                    'identity' => $identity,
                    'selected_tab' => $accountType,
                    'actual_role' => $user->role,
                ]);

                return back()->withErrors([
                    'identity' => "Akun Anda terdaftar sebagai {$userRoleName}. Silakan pilih tab login {$targetTab} untuk masuk.",
                ])->onlyInput('identity', 'account_type');
            }

            // 3. Handle password verification for all users (Siswa, Guru, DUDI)
            if (! Hash::check($password, $user->password)) {
                AuditLogger::log('login_failed_password', ['identity' => $identity]);

                return back()->withErrors([
                    'password' => 'Kata sandi yang Anda masukkan salah.',
                ])->onlyInput('identity', 'account_type');
            }

            if ($user->status !== 'active') {
                AuditLogger::log('login_failed_suspended', ['identity' => $identity]);

                return back()->withErrors(['identity' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan.'])->onlyInput('identity', 'account_type');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            AuditLogger::log($user->isStudent() ? 'login_success_student' : ($user->isTeacher() ? 'login_success_teacher' : 'login_success'), [
                'user_id' => $user->id,
                'role' => $user->role,
            ], $user->id);

            if (session()->has('oauth_return_to')) {
                $returnTo = session()->pull('oauth_return_to');

                return redirect()->to($returnTo);
            }

            return redirect()->intended(route('dashboard'))->with('success', 'Berhasil login sebagai '.$user->name);
        }

        // 4. Try SIJUNA API lookup if teacher user does not exist locally yet (Only for Guru tab)
        if ($accountType === 'guru') {
            try {
                $sijunaService = app(SijunaApiService::class);
                $teacherData = $sijunaService->getTeacherByExternalId($identity);
                if ($teacherData) {
                    $nip = (string) ($teacherData['nip'] ?? $teacherData['external_id'] ?? $teacherData['id'] ?? '');
                    $email = $teacherData['email'] ?? $teacherData['user']['email'] ?? ($nip ? $nip.'@guru.sekolah.id' : $identity);
                    $name = $teacherData['nama'] ?? $teacherData['name'] ?? 'Guru SIJUNA';
                    $phone = $teacherData['hp'] ?? $teacherData['phone'] ?? null;
                    $username = $nip ?? ($teacherData['username'] ?? explode('@', $email)[0]);

                    // Provision teacher user locally with default password
                    $teacherUser = User::create([
                        'external_id' => $nip ?: $email,
                        'username' => $username,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'teacher',
                        'phone' => $phone,
                        'status' => 'active',
                        'password' => Hash::make($password),
                    ]);

                    $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
                    $teacherUser->assignRole($teacherRole);

                    Auth::login($teacherUser, $request->boolean('remember'));
                    $request->session()->regenerate();

                    AuditLogger::log('login_success_teacher_provisioned', [
                        'external_id' => $teacherUser->external_id,
                        'user_id' => $teacherUser->id,
                    ], $teacherUser->id);

                    if (session()->has('oauth_return_to')) {
                        $returnTo = session()->pull('oauth_return_to');

                        return redirect()->to($returnTo);
                    }

                    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang, '.$teacherUser->name);
                }
            } catch (Exception $e) {
                // Silently continue to login failed error below
            }
        }

        // 5. Try SIJUNA API lookup if student user does not exist locally yet (Only for Siswa tab)
        if ($accountType === 'siswa') {
            try {
                $sijunaService = app(SijunaApiService::class);
                $studentData = $sijunaService->getStudentByExternalId($identity);
                if ($studentData) {
                    $nis = (string) ($studentData['nis'] ?? $studentData['external_id'] ?? $studentData['id'] ?? $identity);
                    $email = $studentData['user']['email'] ?? $studentData['email'] ?? ($nis.'@siswa.sekolah.id');
                    $name = $studentData['nama'] ?? $studentData['name'] ?? 'Siswa SIJUNA';
                    $phone = $studentData['hp'] ?? $studentData['phone'] ?? null;

                    // Provision student locally
                    $studentUser = User::create([
                        'external_id' => $nis,
                        'username' => $nis,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'student',
                        'phone' => $phone,
                        'status' => 'active',
                        'password' => Hash::make($password),
                    ]);

                    $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
                    $studentUser->assignRole($studentRole);

                    Auth::login($studentUser, $request->boolean('remember'));
                    $request->session()->regenerate();

                    AuditLogger::log('login_success_student_provisioned', [
                        'external_id' => $studentUser->external_id,
                        'user_id' => $studentUser->id,
                    ], $studentUser->id);

                    if (session()->has('oauth_return_to')) {
                        $returnTo = session()->pull('oauth_return_to');

                        return redirect()->to($returnTo);
                    }

                    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang, '.$studentUser->name);
                }
            } catch (Exception $e) {
                // Silently continue to login failed error below
            }
        }

        AuditLogger::log('login_failed', ['identity' => $identity]);

        $failedMessage = match ($accountType) {
            'guru' => 'Akun Guru dengan NIP, Email, atau Username yang dimasukkan tidak ditemukan/tidak valid.',
            'dudi' => 'Akun DUDI dengan Kode Mitra, Email, atau Username yang dimasukkan tidak ditemukan/tidak valid.',
            default => 'Akun Siswa dengan NIS atau NISN yang dimasukkan tidak ditemukan/tidak valid.',
        };

        return back()->withErrors([
            $identityFieldName => $failedMessage,
            'identity' => $failedMessage,
        ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        AuditLogger::log('logout', [], $userId);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil logout dari Gateway.');
    }

    public function showProfile()
    {
        $user = Auth::user();

        return view('profile.show', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($request->only('name', 'email', 'phone'));

        AuditLogger::log('update_profile', ['fields' => ['name', 'email', 'phone']], $user->id);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log('change_password_success', [], $user->id);

        return back()->with('success', 'Kata sandi Anda berhasil diperbarui.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->with('error', 'Alamat email tidak terdaftar dalam Gateway.');
        }

        AuditLogger::log('forgot_password_request', ['email' => $request->email]);

        return back()->with('status', 'Instruksi pemulihan kata sandi telah dikirim ke email Anda.');
    }
}
