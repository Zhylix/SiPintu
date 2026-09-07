# 🔐 Panduan Praktis Integrasi SSO SiPintu Gateway

Dokumen ini adalah panduan lengkap dan detail untuk mengintegrasikan aplikasi eksternal (Klien / Child App) dengan **SiPintu SSO Gateway**.

---

## ⚠️ Kebijakan Wajib Sinkronisasi Kata Sandi (Password Policy)

> 🚨 **ATURAN WAJIB INTEGRASI (POLICY ENFORCEMENT):**
> 1. **User WAJIB Mengganti Password HANYA di SiPintu Gateway**: Seluruh pengguna non-Admin (`user`, `alumni`, `guru`, `siswa`, `dudi`) **TIDAK BOLEH** mengubah kata sandi di aplikasi downstream/klien. Fitur ubah kata sandi di aplikasi klien wajib dikunci/diarahkan ke SiPintu Gateway.
> 2. **Pengiriman Password via API**: API SiPintu (`/api/v1/user`, `/api/v1/user/profile`, `/oauth/token`) secara otomatis mengirimkan parameter `password` & `password_hash` (bcrypt hash) agar aplikasi klien menyinkronkan kata sandi lokalnya secara langsung.
> 3. **Pengecualian Administrator**: Kebijakan ini berlaku untuk **seluruh role KECUALI ADMIN** (`role: admin`).

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
    "phone": "081234567890",
    "password": "$2y$12$eXaMpLeHaShPaSsWoRdStrInG...",
    "password_hash": "$2y$12$eXaMpLeHaShPaSsWoRdStrInG...",
    "password_sync_required": true,
    "password_change_policy": "MUST_CHANGE_IN_SIPINTU_ONLY",
    "can_change_password_externally": false
}
```

### 2. Logika Pemasangan User & Sinkronisasi Password di Database Lokal (Laravel)

Gunakan `User::updateOrCreate()` pada `OAuthController` aplikasi klien agar akun user baru otomatis dibuat (*Auto-Provisioning*) dan kata sandinya disinkronkan dari SiPintu Gateway:

```php
// Ambil profil user dari SiPintu Gateway
$sipintuUser = $userResponse->json('data') ?? $userResponse->json();

// Pemasangan & Pemetaan User + Sinkronisasi Password ke Database Lokal
$user = User::updateOrCreate(
    ['email' => $sipintuUser['email']], // Identifier utama
    [
        'name'              => $sipintuUser['name'],
        'external_id'       => $sipintuUser['external_id'] ?? null,
        'role'              => $sipintuUser['role'] ?? 'user',
        'password'          => $sipintuUser['password'] ?? bcrypt(Str::random(24)), // Sync password dari SiPintu Gateway API
        'email_verified_at' => now(),
    ]
);

// Pastikan password hash lokal selalu sama dengan password hash SiPintu jika user memperbarui password di SiPintu
if (isset($sipintuUser['password']) && $user->password !== $sipintuUser['password']) {
    $user->update(['password' => $sipintuUser['password']]);
}

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
Buka file `.env` pada aplikasi klien Anda (misal `TESApi`, `CBT`, dll) dan tambahkan:

```env
# ===================================================
# KONEKSI SSO SIPINTU GATEWAY
# ===================================================
SIPINTU_BASE_URL=https://sipintu.smkn1.sch.id
SIPINTU_CLIENT_ID=app_xxxxxxxxxxxx
SIPINTU_CLIENT_SECRET=sec_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
SIPINTU_REDIRECT_URI=https://cbt.smkn1.sch.id/oauth/callback
```

> ⚠️ **Catatan Penting Callback URI:**
> * `SIPINTU_REDIRECT_URI` mengarah ke **domain aplikasi klien Anda sendiri** + `/oauth/callback`.
> * Nilai ini **harus sama persis** dengan yang didaftarkan di SiPintu pada Langkah 1.

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

> 💡 **TIDAK PERLU TOMBOL LOGIN**: Aplikasi klien cukup menyediakan 1 route callback untuk menerima lemparan SSO otomatis dari Portal SiPintu Gateway:

```php
use App\Http\Controllers\OAuthController;

// Endpoint penerima redirect SSO otomatis dari SiPintu Gateway (WAJIB)
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Handle Callback otomatis setelah pengguna mengklik aplikasi di Portal SiPintu
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (! $code) {
            return redirect('/login')->with('error', 'Otorisasi SSO SiPintu gagal: Kode otorisasi tidak ditemukan.');
        }

        $baseUrl      = rtrim(env('SIPINTU_BASE_URL', config('services.sipintu.base_url', 'http://localhost:8000')), '/');
        $clientId     = env('SIPINTU_CLIENT_ID', config('services.sipintu.client_id'));
        $clientSecret = env('SIPINTU_CLIENT_SECRET', config('services.sipintu.client_secret'));
        $redirectUri  = env('SIPINTU_REDIRECT_URI', config('services.sipintu.redirect_uri'));

        // Step 1: Exchange Code dengan Access Token (Backend-to-Backend HTTP POST)
        $response = Http::asForm()->acceptJson()->post("{$baseUrl}/oauth/token", [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error_description') ?? 'Gagal menukarkan Authorization Code ke SiPintu Gateway.';
            return redirect('/login')->with('error', $errorMsg);
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Step 2: Ambil Data Profil Pengguna & Password Hash dari SiPintu Gateway
        $userResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/api/v1/user");

        if ($userResponse->failed()) {
            return redirect('/login')->with('error', 'Gagal mengambil data akun dari SiPintu Gateway.');
        }

        $sipintuUser = $userResponse->json('data') ?? $userResponse->json();

        // Step 3: Autentikasi & Auto-Provisioning Pengguna di Database Lokal
        $user = User::updateOrCreate(
            ['email' => $sipintuUser['email']],
            [
                'name'              => $sipintuUser['name'],
                'external_id'       => $sipintuUser['external_id'] ?? null,
                'role'              => $sipintuUser['role'] ?? 'user',
                // Sinkronisasi password hash dari SiPintu Gateway
                'password'          => $sipintuUser['password'] ?? bcrypt(Str::random(24)),
                'email_verified_at' => now(),
            ]
        );

        // Pastikan hash password lokal selalu sinkron jika user memperbarui password di SiPintu
        if (isset($sipintuUser['password']) && $user->password !== $sipintuUser['password']) {
            $user->update(['password' => $sipintuUser['password']]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        // Langsung masuk ke dashboard aplikasi downstream
        return redirect()->intended('/dashboard')->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * Logout dari sesi lokal
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
