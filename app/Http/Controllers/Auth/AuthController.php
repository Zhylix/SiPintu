<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthRefreshToken;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PasswordSyncService;
use App\Services\SecurityService;
use App\Services\SijunaApiService;
use App\Services\WhatsAppService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $accountType = $request->input('account_type', 'siswa');

        $identity = match ($accountType) {
            'guru' => trim((string) ($request->input('nip') ?: $request->input('identity', ''))),
            'dudi' => trim((string) ($request->input('kode_dudi') ?: $request->input('identity', ''))),
            default => trim((string) ($request->input('nis') ?: ($request->input('email_nis') ?: $request->input('identity', '')))),
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
                default => 'NIS atau Email NIS Siswa',
            };

            return back()->withErrors([
                $identityFieldName => "Masukkan {$label} Anda.",
            ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
        }

        $securityService = app(SecurityService::class);
        if ($securityService->isIpBlocked($request->ip())) {
            $block = $securityService->getActiveBlock($request->ip());
            $expiryText = $block?->expires_at ? $block->expires_at->diffForHumans() : 'beberapa saat';

            return back()->withErrors([
                $identityFieldName => "Akses IP Anda ({$request->ip()}) diblokir sementara karena terlalu banyak percobaan login yang gagal. Silakan coba lagi {$expiryText}.",
            ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
        }

        $password = $request->input('password');

        $isSso = session()->has('oauth_return_to') || $request->boolean('via_sso');

        // 1. Try finding user in Gateway database by email, username, or external_id (SIJUNA)
        $user = User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('external_id', $identity)
            ->first();

        // 1.1 Support login via NIS number when user record stores full email (e.g. 4439 -> 4439@...)
        if (! $user && ! str_contains($identity, '@') && in_array($accountType, ['siswa', 'admin'])) {
            $user = User::where('email', 'like', $identity.'@%')
                ->whereIn('role', ['student', 'siswa', 'alumni'])
                ->first();
        }

        // 1.2 Support login via Email NIS when user record stores NIS in username or external_id
        if (! $user && str_contains($identity, '@') && in_array($accountType, ['siswa', 'admin'])) {
            $nisPrefix = explode('@', $identity)[0];
            if (! empty($nisPrefix)) {
                $user = User::where(function ($q) use ($nisPrefix) {
                    $q->where('external_id', $nisPrefix)
                        ->orWhere('username', $nisPrefix);
                })->whereIn('role', ['student', 'siswa', 'alumni'])->first();
            }
        }

        $adminEnvEmail = strtolower((string) config('auth.admin.email', 'admin@smkn1bangsri.sch.id'));
        $adminEnvUsername = strtolower((string) config('auth.admin.username', 'admin'));
        $allowedAdminIdentities = array_filter(array_unique([
            'admin',
            $adminEnvUsername,
            $adminEnvEmail,
            'admin@smkn1bangsri.sch.id',
            'admin@gateway.sekolah.id',
        ]));

        if (! $user && in_array(strtolower($identity), $allowedAdminIdentities)) {
            $user = User::where('role', 'admin')->first();
        }

        if ($user) {
            // 1. Direct login for Administrator with valid password regardless of active tab (Guru / DUDI / Siswa)
            if ($user->isAdmin()) {
                if (! Hash::check($password, $user->password)) {
                    $securityService->recordFailedLogin($request->ip(), $identity, $user->id);

                    AuditLogger::log($isSso ? 'sso_login_failed_password' : 'login_failed_password', [
                        'identity' => $identity,
                        'via_sso' => $isSso,
                        'is_sso_failure' => $isSso,
                    ]);

                    return back()->withErrors([
                        'password' => 'Kata sandi yang Anda masukkan salah.',
                    ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
                }

                if ($user->status !== 'active') {
                    $securityService->recordFailedLogin($request->ip(), $identity, $user->id);

                    AuditLogger::log($isSso ? 'sso_login_failed_suspended' : 'login_failed_suspended', [
                        'identity' => $identity,
                        'via_sso' => $isSso,
                        'is_sso_failure' => $isSso,
                    ]);

                    return back()->withErrors(['identity' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan.'])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();

                $securityService->recordSuccessfulLogin($request->ip(), $user);

                if ($user->needsSecurityOnboarding()) {
                    session()->flash('security_onboarding_notice', true);
                }

                AuditLogger::log('login_success_admin', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'via_sso' => $isSso,
                ], $user->id);

                if (session()->has('oauth_return_to')) {
                    $returnTo = session()->pull('oauth_return_to');

                    return redirect()->to($returnTo);
                }

                if ($user->must_change_password) {
                    return redirect()->route('profile')
                        ->with('warning', 'Demi keamanan akun Anda, Anda diwajibkan mengubah kata sandi awal terlebih dahulu.')
                        ->with('active_section', 'ganti_password');
                }

                return redirect()->route('admin.dashboard')->with('success', 'Berhasil login sebagai '.$user->name);
            }

            // 2. Strict Role Validation vs Selected Login Tab (Siswa, Guru, DUDI)
            $roleMismatch = false;
            if ($accountType === 'siswa' && ! ($user->isStudent() || $user->isAlumni())) {
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
                    'alumni' => 'Siswa',
                ];
                $targetTab = $tabMap[$user->role] ?? $userRoleName;

                $securityService->recordFailedLogin($request->ip(), $identity, $user->id);

                AuditLogger::log($isSso ? 'sso_login_failed_role_mismatch' : 'login_failed_role_mismatch', [
                    'identity' => $identity,
                    'selected_tab' => $accountType,
                    'actual_role' => $user->role,
                    'via_sso' => $isSso,
                    'is_sso_failure' => $isSso,
                ]);

                return back()->withErrors([
                    $identityFieldName => "Akun Anda terdaftar sebagai {$userRoleName}. Silakan pilih tab login {$targetTab} untuk masuk.",
                ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
            }

            // 3. Handle password verification for all users (Siswa, Guru, DUDI)
            if (! Hash::check($password, $user->password)) {
                $securityService->recordFailedLogin($request->ip(), $identity, $user->id);

                AuditLogger::log($isSso ? 'sso_login_failed_password' : 'login_failed_password', [
                    'identity' => $identity,
                    'via_sso' => $isSso,
                    'is_sso_failure' => $isSso,
                ]);

                return back()->withErrors([
                    'password' => 'Kata sandi yang Anda masukkan salah.',
                ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
            }

            if ($user->status !== 'active') {
                $securityService->recordFailedLogin($request->ip(), $identity, $user->id);

                AuditLogger::log($isSso ? 'sso_login_failed_suspended' : 'login_failed_suspended', [
                    'identity' => $identity,
                    'via_sso' => $isSso,
                    'is_sso_failure' => $isSso,
                ]);

                return back()->withErrors([
                    $identityFieldName => 'Akun Anda sedang dinonaktifkan atau ditangguhkan.',
                ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            $securityService->recordSuccessfulLogin($request->ip(), $user);

            if ($user->needsSecurityOnboarding()) {
                session()->flash('security_onboarding_notice', true);
            }

            AuditLogger::log($user->isStudent() ? 'login_success_student' : ($user->isTeacher() ? 'login_success_teacher' : 'login_success'), [
                'user_id' => $user->id,
                'role' => $user->role,
                'via_sso' => $isSso,
            ], $user->id);

            if (session()->has('oauth_return_to')) {
                $returnTo = session()->pull('oauth_return_to');

                return redirect()->to($returnTo);
            }

            if ($user->must_change_password) {
                return redirect()->route('profile')
                    ->with('warning', 'Demi keamanan akun Anda, Anda diwajibkan mengubah kata sandi awal terlebih dahulu.')
                    ->with('active_section', 'ganti_password');
            }

            return redirect()->intended(route('dashboard'))->with('success', 'Berhasil login sebagai '.$user->name);
        }

        // 4. Try SIJUNA API lookup if teacher user does not exist locally yet (Only for Guru tab)
        if ($accountType === 'guru') {
            try {
                $sijunaService = app(SijunaApiService::class);
                $teacherData = $sijunaService->getTeacherByExternalId($identity);
                if ($teacherData) {
                    if ($password !== 'password') {
                        $securityService->recordFailedLogin($request->ip(), $identity);

                        return back()->withErrors([
                            'password' => 'Akun Guru Anda terdaftar di SIJUNA tetapi baru pertama kali masuk ke SiPintu Gateway. Silakan gunakan kata sandi awal ("password") untuk masuk.',
                        ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
                    }

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
                        'password' => Hash::make('password'),
                    ]);

                    $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
                    $teacherUser->assignRole($teacherRole);

                    Auth::login($teacherUser, $request->boolean('remember'));
                    $request->session()->regenerate();

                    $securityService->recordSuccessfulLogin($request->ip(), $teacherUser);
                    session()->flash('security_onboarding_notice', true);

                    AuditLogger::log('login_success_teacher_provisioned', [
                        'external_id' => $teacherUser->external_id,
                        'user_id' => $teacherUser->id,
                        'via_sso' => $isSso,
                    ], $teacherUser->id);

                    if (session()->has('oauth_return_to')) {
                        $returnTo = session()->pull('oauth_return_to');

                        return redirect()->to($returnTo);
                    }

                    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang, '.$teacherUser->name.'. Silakan ganti kata sandi awal Anda.');
                }
            } catch (Exception $e) {
                // Silently continue to login failed error below
            }
        }

        // 5. Try SIJUNA API lookup if student user does not exist locally yet (Only for Siswa tab)
        if ($accountType === 'siswa') {
            try {
                $sijunaService = app(SijunaApiService::class);
                $nisSearch = str_contains($identity, '@') ? explode('@', $identity)[0] : $identity;
                $studentData = $sijunaService->getStudentByExternalId($identity)
                    ?: ($nisSearch !== $identity ? $sijunaService->getStudentByExternalId($nisSearch) : null);
                if ($studentData) {
                    if ($password !== 'password') {
                        $securityService->recordFailedLogin($request->ip(), $identity);

                        return back()->withErrors([
                            'password' => 'Akun Siswa Anda terdaftar di SIJUNA tetapi baru pertama kali masuk ke SiPintu Gateway. Silakan gunakan kata sandi awal ("password") untuk masuk.',
                        ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
                    }

                    $nis = (string) ($studentData['nis'] ?? $studentData['external_id'] ?? $studentData['id'] ?? $nisSearch);
                    $email = $studentData['user']['email'] ?? $studentData['email'] ?? ($nis.'@smkn1bangsri.sch.id');
                    $name = $studentData['nama'] ?? $studentData['name'] ?? 'Siswa SIJUNA';
                    $phone = $studentData['hp'] ?? $studentData['phone'] ?? null;

                    // Provision student locally with default password
                    $studentUser = User::create([
                        'external_id' => $nis,
                        'username' => $nis,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'student',
                        'phone' => $phone,
                        'status' => 'active',
                        'password' => Hash::make('password'),
                    ]);

                    $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
                    $studentUser->assignRole($studentRole);

                    Auth::login($studentUser, $request->boolean('remember'));
                    $request->session()->regenerate();

                    $securityService->recordSuccessfulLogin($request->ip(), $studentUser);
                    session()->flash('security_onboarding_notice', true);

                    AuditLogger::log('login_success_student_provisioned', [
                        'external_id' => $studentUser->external_id,
                        'user_id' => $studentUser->id,
                        'via_sso' => $isSso,
                    ], $studentUser->id);

                    if (session()->has('oauth_return_to')) {
                        $returnTo = session()->pull('oauth_return_to');

                        return redirect()->to($returnTo);
                    }

                    return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang, '.$studentUser->name.'. Silakan ganti kata sandi awal Anda.');
                }
            } catch (Exception $e) {
                // Silently continue to login failed error below
            }
        }

        $securityService->recordFailedLogin($request->ip(), $identity);

        AuditLogger::log($isSso ? 'sso_login_failed' : 'login_failed', [
            'identity' => $identity,
            'via_sso' => $isSso,
            'is_sso_failure' => $isSso,
        ]);

        $failedMessage = match ($accountType) {
            'guru' => 'Akun Guru dengan NIP, Email, atau Username yang dimasukkan tidak ditemukan/tidak valid.',
            'dudi' => 'Akun DUDI dengan Kode Mitra, Email, atau Username yang dimasukkan tidak ditemukan/tidak valid.',
            default => 'Akun Siswa dengan NIS atau NISN yang dimasukkan tidak ditemukan/tidak valid.',
        };

        return back()->withErrors([
            $identityFieldName => $failedMessage,
        ])->onlyInput('account_type', 'nis', 'nip', 'kode_dudi', 'identity');
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if ($userId) {
            AuditLogger::log('logout', [], $userId);

            // Single Sign-Out (Global Logout): Invalidate and revoke all active OAuth tokens and auth codes
            OAuthAccessToken::where('user_id', $userId)->update(['revoked' => true]);
            OAuthRefreshToken::whereHas('accessToken', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->update(['revoked' => true]);
            OAuthAuthCode::where('user_id', $userId)->update(['revoked' => true]);

            // Dispatch backchannel logout notification to downstream applications if configured
            $appsWithLogout = Application::whereNotNull('logout_uri')
                ->where('logout_uri', '!=', '')
                ->where('status', 'active')
                ->get();

            foreach ($appsWithLogout as $app) {
                try {
                    Http::timeout(3)->post($app->logout_uri, [
                        'event' => 'user_logout',
                        'user_id' => $userId,
                        'client_id' => $app->client_id,
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Throwable $e) {
                    // Fail silently so downstream offline app doesn't block local logout
                    Log::debug("Backchannel logout notice to {$app->name} skipped: ".$e->getMessage());
                }
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil logout dari seluruh sesi Gateway.');
    }

    public function showProfile(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        // 1. Fetch recent audit logs for security transparency
        $auditLogs = AuditLog::where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        // 2. Filter accessible apps for Single Sign-On launcher
        $accessibleApps = Application::where('status', 'active')
            ->get()
            ->filter(function ($app) use ($user) {
                return $user->canAccessApplication($app);
            });

        return view('profile.show', compact('user', 'auditLogs', 'accessibleApps'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $activeSection = $request->input('active_section');

        // 1. WhatsApp Section Update
        if ($activeSection === 'whatsapp') {
            $request->validate([
                'phone' => ['nullable', 'string', 'max:30', 'regex:/^(\+?[0-9\s\-()]{8,25})?$/'],
                'wa_notify' => ['nullable', 'boolean'],
            ], [
                'phone.regex' => 'Format nomor WhatsApp tidak valid. Masukkan nomor telepon yang valid (contoh: 08123456789 atau +628123456789).',
                'phone.max' => 'Nomor WhatsApp maksimal 30 karakter.',
            ]);

            try {
                $rawPhone = $request->phone ? trim((string) $request->phone) : null;
                $cleanPhone = null;
                if ($rawPhone !== null && $rawPhone !== '') {
                    // Normalize phone number: allow only numbers and optional leading +
                    $cleanPhone = preg_replace('/[^\d+]/', '', $rawPhone);
                }

                $updateData = [
                    'phone' => $cleanPhone,
                ];

                if (Schema::hasColumn('users', 'wa_notify') && $request->has('wa_notify')) {
                    $updateData['wa_notify'] = $request->boolean('wa_notify');
                }

                $user->update($updateData);

                AuditLogger::log('update_profile_whatsapp', ['fields' => array_keys($updateData)], $user->id);

                return back()
                    ->with('success', 'Pengaturan nomor WhatsApp berhasil diperbarui.')
                    ->with('active_section', 'whatsapp');
            } catch (\Throwable $e) {
                Log::error("[AuthController] Gagal menyimpan nomor WhatsApp untuk user {$user->id}: ".$e->getMessage(), [
                    'exception' => $e,
                ]);

                return back()
                    ->with('error', 'Terjadi kendala saat memperbarui nomor WhatsApp: '.$e->getMessage())
                    ->with('active_section', 'whatsapp');
            }
        }

        // 2. Email Section Update
        if ($activeSection === 'email') {
            $request->validate([
                'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            ], [
                'email.unique' => 'Email ini sudah digunakan oleh pengguna lain.',
            ]);

            $updateData = [
                'email' => trim((string) $request->email),
            ];

            $user->update($updateData);

            AuditLogger::log('update_profile_email', ['fields' => array_keys($updateData)], $user->id);

            return back()
                ->with('success', 'Alamat email berhasil diperbarui.')
                ->with('active_section', 'email');
        }

        // 3. Nama Lengkap Section Update
        if ($activeSection === 'nama_lengkap') {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $updateData = [
                'name' => trim((string) $request->name),
            ];

            $user->update($updateData);

            AuditLogger::log('update_profile_name', ['fields' => array_keys($updateData)], $user->id);

            return back()
                ->with('success', 'Nama lengkap berhasil diperbarui.')
                ->with('active_section', 'nama_lengkap');
        }

        // 4. Fallback for General / Full Profile Update
        if (! $request->filled('name')) {
            $request->merge(['name' => $user->name]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'wa_notify' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Email ini sudah digunakan oleh pengguna lain.',
            'username.unique' => 'Username ini sudah digunakan oleh pengguna lain.',
        ]);

        $updateData = [
            'name' => trim((string) $request->name),
            'email' => trim((string) $request->email),
            'username' => filled($request->username) ? trim((string) $request->username) : null,
            'phone' => filled($request->phone) ? trim((string) $request->phone) : null,
        ];

        if (Schema::hasColumn('users', 'wa_notify') && $request->has('wa_notify')) {
            $updateData['wa_notify'] = $request->boolean('wa_notify');
        }

        $user->update($updateData);

        AuditLogger::log('update_profile', ['fields' => array_keys($updateData)], $user->id);

        return back()
            ->with('success', 'Informasi profil Anda berhasil diperbarui.')
            ->with('active_section', $activeSection ?? 'nama_lengkap');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'avatar.required' => 'Pilih berkas foto profil terlebih dahulu.',
            'avatar.image' => 'Berkas harus berupa gambar.',
            'avatar.mimes' => 'Format foto harus JPEG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran berkas foto profil awal maksimal 5 MB.',
            'avatar.uploaded' => 'Berkas foto profil gagal diunggah. Pastikan ukuran berkas tidak melebihi batas upload server (maksimal 5 MB).',
        ]);

        if ($user->avatar && ! filter_var($user->avatar, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $this->optimizeAndStoreAvatar($request->file('avatar'));
        $user->update(['avatar' => $path]);

        AuditLogger::log('update_avatar', ['avatar_path' => $path], $user->id);

        return back()
            ->with('success', 'Foto profil berhasil diunggah dan dioptimasi!')
            ->with('active_section', 'nama_lengkap');
    }

    /**
     * Crop, resize, and compress uploaded avatar to 400x400 WebP format for ultra-fast web delivery.
     */
    private function optimizeAndStoreAvatar($file): string
    {
        $targetSize = 400;
        $quality = 80;

        $filename = 'avatars/'.Str::random(40).'.webp';

        $sourceImagePath = $file->getRealPath();
        $imageInfo = @getimagesize($sourceImagePath);

        if (! $imageInfo) {
            return $file->store('avatars', 'public');
        }

        $mime = $imageInfo['mime'] ?? '';
        $srcImage = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourceImagePath),
            'image/png' => @imagecreatefrompng($sourceImagePath),
            'image/webp' => @imagecreatefromwebp($sourceImagePath),
            'image/gif' => @imagecreatefromgif($sourceImagePath),
            default => null,
        };

        if (! $srcImage) {
            return $file->store('avatars', 'public');
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        // Center square crop calculations
        $cropSize = min($origWidth, $origHeight);
        $cropX = (int) (($origWidth - $cropSize) / 2);
        $cropY = (int) (($origHeight - $cropSize) / 2);

        $dstImage = imagecreatetruecolor($targetSize, $targetSize);

        // Retain alpha channel for PNG/WebP
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $targetSize, $targetSize, $transparent);

        imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0,
            $cropX, $cropY,
            $targetSize, $targetSize,
            $cropSize, $cropSize
        );

        ob_start();
        imagewebp($dstImage, null, $quality);
        $imageContents = ob_get_clean();

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        Storage::disk('public')->put($filename, $imageContents);

        return $filename;
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->avatar && ! filter_var($user->avatar, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        AuditLogger::log('delete_avatar', [], $user->id);

        return back()
            ->with('info', 'Foto profil telah dihapus dan dikembalikan ke inisial nama.')
            ->with('active_section', 'nama_lengkap');
    }

    public function syncSijunaProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->external_id && ! in_array($user->role, ['student', 'teacher', 'siswa', 'guru'])) {
            return back()
                ->with('error', 'Akun Anda tidak terhubung ke SIJUNA API.')
                ->with('active_section', 'sijuna');
        }

        try {
            $sijunaService = app(SijunaApiService::class);
            $identifier = $user->external_id ?: ($user->username ?: $user->email);
            $externalData = null;

            if ($user->isTeacher()) {
                $externalData = $sijunaService->getTeacherByExternalId($identifier);
            } else {
                $externalData = $sijunaService->getStudentByExternalId($identifier);
            }

            if ($externalData) {
                $updatedFields = [];
                $newName = $externalData['nama'] ?? $externalData['name'] ?? null;
                $newEmail = $externalData['email'] ?? $externalData['user']['email'] ?? null;
                $newPhone = $externalData['hp'] ?? $externalData['phone'] ?? null;

                if ($newName && $newName !== $user->name) {
                    $user->name = $newName;
                    $updatedFields[] = 'nama';
                }
                if ($newEmail && $newEmail !== $user->email && ! User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
                    $user->email = $newEmail;
                    $updatedFields[] = 'email';
                }
                if ($newPhone && $newPhone !== $user->phone) {
                    $user->phone = $newPhone;
                    $updatedFields[] = 'no. hp';
                }

                $user->save();

                AuditLogger::log('sync_sijuna_profile_success', ['updated' => $updatedFields], $user->id);

                return back()
                    ->with('success', 'Profil berhasil disinkronkan secara realtime dengan SIJUNA API.')
                    ->with('active_section', 'sijuna');
            }

            return back()
                ->with('info', 'Data profil Anda di SIJUNA API sudah dalam kondisi yang paling baru.')
                ->with('active_section', 'sijuna');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Gagal terhubung ke SIJUNA API: '.$e->getMessage())
                ->with('active_section', 'sijuna');
        }
    }

    public function updateNotificationSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (Schema::hasColumn('users', 'wa_notify')) {
            $user->update([
                'wa_notify' => $request->boolean('wa_notify'),
            ]);

            AuditLogger::log('update_notification_settings', ['wa_notify' => $user->wa_notify], $user->id);
        }

        return back()
            ->with('success', 'Pengaturan notifikasi WhatsApp berhasil disimpan.')
            ->with('active_section', 'whatsapp');
    }

    public function sendTestWhatsapp(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (empty($user->phone)) {
            return back()
                ->with('error', 'Nomor telepon / WhatsApp Anda belum diisi.')
                ->with('active_section', 'whatsapp');
        }

        $waService = app(WhatsAppService::class);
        $message = "[NOTIFIKASI SIPINTU]\n\nHalo *{$user->name}*,\n\nIni adalah pesan konfirmasi bahwa nomor WhatsApp Anda (_{$user->phone}_) telah terhubung secara sukses dengan sistem *SiPintu Gateway SMKN 1 Bangsri*.\n\nWaktu tes: ".now()->format('d M Y H:i:s').' WIB.';

        $result = $waService->sendMessage($user->phone, $message);

        if ($result['success']) {
            AuditLogger::log('test_whatsapp_sent_success', ['phone' => $user->phone], $user->id);

            return back()
                ->with('success', 'Pesan uji coba WhatsApp berhasil dikirim ke nomor '.$user->phone)
                ->with('active_section', 'whatsapp');
        }

        AuditLogger::log('test_whatsapp_sent_failed', ['error' => $result['error'] ?? 'Unknown'], $user->id);

        return back()
            ->with('error', 'Gagal mengirim WhatsApp: '.($result['error'] ?? 'Layanan WhatsApp sedang offline.'))
            ->with('active_section', 'whatsapp');
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
            'must_change_password' => false,
        ]);

        // Broadcast password change to connected downstream SSO applications (KEC ADMIN)
        app(PasswordSyncService::class)->broadcastPasswordChange($user);

        AuditLogger::log('change_password_success', [], $user->id);

        return back()
            ->with('success', 'Kata sandi Anda berhasil diperbarui di SiPintu Gateway dan telah disinkronkan ke seluruh aplikasi terhubung.')
            ->with('active_section', 'ganti_password');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            AuditLogger::log('forgot_password_request_sent', ['email' => $request->email]);

            return back()->with('status', 'Instruksi pemulihan kata sandi telah dikirim ke email Anda.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Send OTP to user's registered WhatsApp number for fast password reset.
     */
    public function sendResetOtpWhatsapp(Request $request): RedirectResponse
    {
        $request->validate([
            'identity' => ['required', 'string'],
        ], [
            'identity.required' => 'Masukkan NIS, NIP, Email, atau Username Anda.',
        ]);

        $identity = trim((string) $request->identity);
        $user = User::where('email', $identity)
            ->orWhere('username', $identity)
            ->orWhere('external_id', $identity)
            ->first();

        // Support NIS lookup by prefix
        if (! $user && ! str_contains($identity, '@')) {
            $user = User::where('email', 'like', $identity.'@%')->first();
        }

        if (! $user) {
            return back()->withErrors(['identity' => 'Akun dengan NIS/NIP/Email tersebut tidak ditemukan di sistem.'])->withInput();
        }

        if (empty($user->phone)) {
            return back()->withErrors([
                'identity' => 'Akun Anda belum memiliki nomor WhatsApp terdaftar. Silakan hubungi admin sekolah atau gunakan pemulihan via email.',
            ])->withInput();
        }

        $whatsAppService = app(WhatsAppService::class);
        $cleanPhone = $whatsAppService->formatPhoneNumber($user->phone);

        if (! $cleanPhone) {
            return back()->withErrors([
                'identity' => 'Format nomor WhatsApp Anda tidak valid. Silakan hubungi admin sekolah.',
            ])->withInput();
        }

        // Generate 6 digit numeric OTP
        $otp = (string) random_int(100000, 999999);
        $otpKey = "wa_reset_otp:{$user->id}";

        Cache::put($otpKey, [
            'otp' => $otp,
            'phone' => $cleanPhone,
            'user_id' => $user->id,
        ], 300);

        $maskedPhone = substr($cleanPhone, 0, 4).'****'.substr($cleanPhone, -3);
        $message = "🔐 *KODE VERIFIKASI SIPINTU*\n\n".
            "Halo, *{$user->name}*.\n\n".
            "Kode OTP untuk reset kata sandi akun SiPintu Anda adalah:\n\n".
            "👉 *{$otp}*\n\n".
            "Kode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapapun demi keamanan akun Anda.\n\n".
            '_SiPintu Identity Gateway - SMKN 1 Bangsri_';

        $res = $whatsAppService->sendMessage($cleanPhone, $message);

        if (! ($res['success'] ?? false)) {
            Log::warning("[WhatsApp OTP] Gagal mengirim pesan WA ke {$cleanPhone}: ".($res['error'] ?? ''));

            return back()->withErrors([
                'identity' => 'Gagal mengirim OTP WhatsApp: '.($res['error'] ?? 'Layanan WhatsApp Bot sedang offline. Coba lagi nanti atau gunakan pemulihan email.'),
            ])->withInput();
        }

        AuditLogger::log('wa_otp_reset_requested', ['phone' => $maskedPhone], $user->id);

        return redirect()->route('password.whatsapp.verify_form', ['uid' => $user->id])
            ->with('status', "Kode OTP 6 digit telah dikirimkan ke nomor WhatsApp Anda ({$maskedPhone}).");
    }

    /**
     * Show form to verify OTP and enter new password.
     */
    public function showVerifyOtp(Request $request)
    {
        $userId = $request->query('uid');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('password.request')->withErrors(['identity' => 'Sesi pemulihan tidak valid.']);
        }

        $otpData = Cache::get("wa_reset_otp:{$user->id}");
        if (! $otpData) {
            return redirect()->route('password.request')->withErrors(['identity' => 'Kode OTP Anda telah kedaluwarsa (lebih dari 5 menit). Silakan ajukan ulang.']);
        }

        $cleanPhone = (string) $user->phone;
        $maskedPhone = strlen($cleanPhone) >= 7 ? (substr($cleanPhone, 0, 4).'****'.substr($cleanPhone, -3)) : $cleanPhone;

        return view('auth.verify-otp', [
            'user' => $user,
            'maskedPhone' => $maskedPhone,
        ]);
    }

    /**
     * Verify OTP and reset user password.
     */
    public function verifyResetOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'otp' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 digit angka.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = User::findOrFail($request->user_id);
        $otpKey = "wa_reset_otp:{$user->id}";
        $cached = Cache::get($otpKey);

        if (! $cached || ! hash_equals((string) $cached['otp'], trim((string) $request->otp))) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau telah kedaluwarsa. Silakan periksa kembali pesan WhatsApp Anda.'])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        Cache::forget($otpKey);

        // Broadcast to downstream apps
        app(PasswordSyncService::class)->broadcastPasswordChange($user);

        AuditLogger::log('wa_otp_reset_success', [], $user->id);

        return redirect()->route('login')->with('success', 'Kata sandi akun Anda berhasil diperbarui melalui verifikasi WhatsApp! Silakan login dengan kata sandi baru Anda.');
    }

    /**
     * Invalidate other sessions for the authenticated user.
     */
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Masukkan kata sandi akun Anda untuk konfirmasi.',
            'password.current_password' => 'Kata sandi tidak sesuai.',
        ]);

        Auth::logoutOtherDevices($request->password);

        AuditLogger::log('logout_other_devices', [], Auth::id());

        return back()
            ->with('success', 'Berhasil mengeluarkan akun Anda dari semua sesi perangkat lain!')
            ->with('active_section', 'perangkat_login');
    }
}
