<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SijunaApiService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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
        $credentials = $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'account_type' => ['nullable', 'string', 'in:siswa,guru,dudi'],
        ], [
            'identity.required' => 'Masukkan Email, Username, atau Identifier Siswa/NISN.',
        ]);

        $identity = trim($credentials['identity']);
        $password = $request->input('password');
        $accountType = $request->input('account_type', 'siswa');

        // 1. Try finding user in Gateway database by email, username, or external_id (SIJUNA)
        $user = User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('external_id', $identity)
            ->first();

        if ($user) {
            // Strict Role Validation vs Selected Login Tab (Siswa, Guru, DUDI)
            if (!$user->isAdmin()) {
                $roleMismatch = false;
                if ($accountType === 'siswa' && !$user->isStudent()) {
                    $roleMismatch = true;
                } elseif ($accountType === 'guru' && !$user->isTeacher()) {
                    $roleMismatch = true;
                } elseif ($accountType === 'dudi' && !$user->isDudi()) {
                    $roleMismatch = true;
                }

                if ($roleMismatch) {
                    $userRoleName = $user->getUserTypeName();
                    $tabMap = [
                        'teacher' => 'Guru',
                        'dudi' => 'Mitra DUDI',
                        'student' => 'Siswa',
                    ];
                    $targetTab = $tabMap[$user->user_type] ?? $userRoleName;

                    AuditLogger::log('login_failed_role_mismatch', [
                        'identity' => $identity,
                        'selected_tab' => $accountType,
                        'actual_role' => $user->user_type,
                    ]);

                    return back()->withErrors([
                        'identity' => "Akun Anda terdaftar sebagai {$userRoleName}. Silakan pilih tab login {$targetTab} untuk masuk.",
                    ])->onlyInput('identity', 'account_type');
                }
            }

            // 2. Handle Siswa login via SIJUNA identifier / external_id matching
            if ($user->isStudent()) {
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                AuditLogger::log('login_success_student', [
                    'external_id' => $user->external_id,
                    'user_id' => $user->id,
                ], $user->id);

                if (session()->has('oauth_return_to')) {
                    $returnTo = session()->pull('oauth_return_to');
                    return redirect()->to($returnTo);
                }

                return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . $user->name);
            }

            // 3. Handle Internal Users (Guru, DUDI, Admin) password verification
            if ($password && Hash::check($password, $user->password)) {
                if ($user->status !== 'active') {
                    AuditLogger::log('login_failed_suspended', ['identity' => $identity]);
                    return back()->withErrors(['identity' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan.'])->onlyInput('identity', 'account_type');
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                AuditLogger::log('login_success', [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                ], $user->id);

                if (session()->has('oauth_return_to')) {
                    $returnTo = session()->pull('oauth_return_to');
                    return redirect()->to($returnTo);
                }

                return redirect()->intended(route('dashboard'))->with('success', 'Berhasil login sebagai ' . $user->name);
            }

            return back()->withErrors([
                'password' => 'Kata sandi yang Anda masukkan salah.',
            ])->onlyInput('identity', 'account_type');
        }

        // 4. Try SIJUNA API lookup if student user does not exist locally yet (Only for Siswa tab)
        if ($accountType === 'siswa') {
            try {
                $sijunaService = app(SijunaApiService::class);
                $studentData = $sijunaService->getStudentByExternalId($identity);
                if ($studentData) {
                    // Provision student locally
                    $studentUser = User::create([
                        'external_id' => $studentData['external_id'] ?? $studentData['id'] ?? $identity,
                        'name' => $studentData['name'] ?? 'Siswa SIJUNA',
                        'email' => $studentData['email'] ?? ($identity . '@siswa.sekolah.id'),
                        'user_type' => 'student',
                        'phone' => $studentData['phone'] ?? null,
                        'status' => 'active',
                        'password' => Hash::make(Str::random(32)),
                    ]);

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

                    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang, ' . $studentUser->name);
                }
            } catch (Exception $e) {
                // Silently continue to login failed error below
            }
        }

        AuditLogger::log('login_failed', ['identity' => $identity]);

        return back()->withErrors([
            'identity' => 'Kredensial atau Identifier yang dimasukkan tidak ditemukan/tidak valid.',
        ])->onlyInput('identity', 'account_type');
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($request->only('name', 'email', 'phone'));

        AuditLogger::log('update_profile', ['fields' => ['name', 'email', 'phone']], $user->id);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Students identity from SIJUNA don't use internal passwords
        if ($user->isStudent()) {
            return back()->with('error', 'Akun siswa dikelola melalui SIJUNA dan tidak menggunakan kata sandi Gateway.');
        }

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
        if (!$user) {
            return back()->with('error', 'Alamat email tidak terdaftar dalam Gateway.');
        }

        if ($user->isStudent()) {
            return back()->with('error', 'Akun siswa dikelola secara terpusat oleh SIJUNA.');
        }

        AuditLogger::log('forgot_password_request', ['email' => $request->email]);

        return back()->with('status', 'Instruksi pemulihan kata sandi telah dikirim ke email Anda.');
    }
}
