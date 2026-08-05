# 🔐 Panduan Praktis Integrasi SSO SiPintu Gateway

Dokumen ini adalah panduan lengkap dan detail untuk mengintegrasikan aplikasi eksternal (Klien / Child App) dengan **SiPintu SSO Gateway**.

---

## 👤 Pemasangan & Pemetaan User Lokal (User Auto-Provisioning)

Ketika pengguna berhasil melakukan login SSO melalui SiPintu Gateway, aplikasi klien akan menerima data akun dari endpoint `GET /api/v1/user`. Data ini digunakan untuk mendaftarkan atau menyinkronkan user ke database lokal aplikasi klien.

### 1. Struktur JSON Data User dari SiPintu Gateway (`/api/v1/user`)

```json
{
    "id": "15",
    "external_id": "1234567890",
    "name": "Ahmad Fauzi",
    "email": "ahmad@sijuna.sch.id",
    "role": "student",
    "phone": "081234567890"
}
```

### 2. Logika Pemasangan User di Database Lokal (Laravel)

Gunakan `User::updateOrCreate()` pada `OAuthController` aplikasi klien agar akun user baru otomatis dibuat (*Auto-Provisioning*) dan akun lama selalu ter-update:

```php
// Ambil profil user dari SiPintu Gateway
$sipintuUser = $userResponse->json('data') ?? $userResponse->json();

// Pemasangan & Pemetaan User ke Database Lokal
$user = User::updateOrCreate(
    ['email' => $sipintuUser['email']], // Identifier utama
    [
        'name'              => $sipintuUser['name'],
        'external_id'       => $sipintuUser['external_id'] ?? null,
        'role'              => $sipintuUser['role'] ?? 'user',
        'password'          => bcrypt(Str::random(24)), // Random password karena autentikasi dihandle penuh oleh SSO
        'email_verified_at' => now(),
    ]
);

// Loginkan user ke sesi lokal aplikasi klien
Auth::login($user, true);
```

---

## ⚡ Quickstart (Pemasangan Instan 3 Langkah)

### Langkah 1: Buat Kredensial SSO dari Terminal SiPintu
Buka terminal di folder `SiPintu` dan jalankan perintah Artisan berikut:

```bash
php artisan sipintu:sso-client "Nama Aplikasi Anda" --redirect=http://localhost:8001/oauth/callback --base-url=http://localhost:8001
```

> **Hasil Output:** Anda akan mendapatkan **Client ID** (`app_...`) dan **Client Secret** (`sec_...`) secara otomatis.

---

### Langkah 2: Pasang Variabel Lingkungan di Aplikasi Klien (`.env`)
Buka file `.env` pada aplikasi klien Anda (misal `TESApi`) dan tambahkan:

```env
minta ke SIR HELMY YUNAN NASUTION UNTUK CLIENT ID DAN CLIENT SECRET DAN BASE URL DAN REDIRECT URI
```

---


## 🛠️ Perintah Artisan SSO SiPintu Gateway

SiPintu Gateway dilengkapi dengan CLI helper terintegrasi:

| Perintah | Deskripsi |
| :--- | :--- |
| `php artisan sipintu:sso-client` | Mendaftarkan aplikasi SSO baru & menampilkan konfigurasi `.env` instan |
| `php artisan sipintu:sso-list` | Menampilkan tabel seluruh aplikasi klien yang terdaftar di database |
| `php artisan sipintu:sso-health` | Menguji routing, endpoint OpenID, JWKS, dan memverifikasi bypassing CSRF |

---

## 📖 Panduan Kode Lengkap Integrasi Klien (Laravel)

### 1. Route Definition (`routes/web.php` di Aplikasi Klien)

```php
use App\Http\Controllers\OAuthController;

Route::get('/login/sipintu', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::post('/logout', [OAuthController::class, 'logout'])->name('logout');
```

---

### 2. Implementation Controller (`app/Http/Controllers/OAuthController.php`)

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Step 1: Redirect pengguna ke halaman Login SiPintu Gateway
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        // Cookie fallback untuk stabilitas lintas port (misal localhost:8000 ke localhost:8001)
        Cookie::queue('oauth_state', $state, 10, null, null, false, true);

        $query = http_build_query([
            'client_id'     => config('services.sipintu.client_id', env('SIPINTU_CLIENT_ID')),
            'redirect_uri'  => config('services.sipintu.redirect_uri', env('SIPINTU_REDIRECT_URI')),
            'response_type' => 'code',
            'scope'         => 'openid profile email',
            'state'         => $state,
        ]);

        $baseUrl = config('services.sipintu.base_url', env('SIPINTU_BASE_URL', 'http://localhost:8000'));

        return redirect()->away("{$baseUrl}/oauth/authorize?{$query}");
    }

    /**
     * Step 2: Handle Callback setelah disetujui di SiPintu
     */
    public function callback(Request $request)
    {
        $sessionState = $request->session()->pull('oauth_state');
        $cookieState  = $request->cookie('oauth_state');
        $requestState = $request->input('state');

        // Validasi Anti-CSRF State Parameter
        $validState = ($requestState && ($requestState === $sessionState || $requestState === $cookieState));

        if (! $validState) {
            return redirect()->route('login')->with('error', 'Validasi State OAuth gagal (CSRF Protection).');
        }

        $code = $request->input('code');
        $baseUrl = config('services.sipintu.base_url', env('SIPINTU_BASE_URL', 'http://localhost:8000'));

        // Step 3: Exchange Code dengan Access Token
        $response = Http::asForm()->acceptJson()->post("{$baseUrl}/oauth/token", [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.sipintu.client_id', env('SIPINTU_CLIENT_ID')),
            'client_secret' => config('services.sipintu.client_secret', env('SIPINTU_CLIENT_SECRET')),
            'redirect_uri'  => config('services.sipintu.redirect_uri', env('SIPINTU_REDIRECT_URI')),
            'code'          => $code,
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error_description') ?? 'Gagal menukarkan Authorization Code.';
            return redirect()->route('login')->with('error', $errorMsg);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Step 4: Ambil Profil Pengguna
        $userResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/api/v1/user");

        if ($userResponse->failed()) {
            return redirect()->route('login')->with('error', 'Gagal mengambil data akun dari SiPintu Gateway.');
        }

        $sipintuUser = $userResponse->json('data') ?? $userResponse->json();

        // Step 5: Autentikasi Pengguna di Aplikasi Lokal
        $user = User::updateOrCreate(
            ['email' => $sipintuUser['email']],
            [
                'name'              => $sipintuUser['name'],
                'password'          => bcrypt(Str::random(24)),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended('/dashboard')->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * Step 6: Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('info', 'Anda telah keluar dari aplikasi.');
    }
}
```
---

## ❓ Troubleshoot & Solusi Masalah

| Kendala / Error | Penyebab | Solusi |
| :--- | :--- | :--- |
| `CSRF token mismatch.` | POST `/oauth/token` terkena middleware CSRF browser. | Jalankan `php artisan sipintu:sso-health` dan atur `PreventRequestForgery::except(['oauth/*'])` di Gateway. |
| `Validasi State OAuth gagal` | Cookie/Session terhapus saat berpindah port (`localhost:8000` ke `8001`). | Gunakan metode ganda (Session + Cookie fallback) seperti pada contoh `OAuthController.php` di atas. |
| `invalid_client` | Client ID atau Client Secret tidak cocok dengan database SiPintu. | Jalankan `php artisan sipintu:sso-list` untuk mencocokkan kredensial. |
| `invalid_grant` | Authorization Code sudah kadaluarsa (berlaku 5 menit) atau sudah pernah ditukarkan. | Lakukan alur login dari awal untuk mendapatkan `code` baru. |
