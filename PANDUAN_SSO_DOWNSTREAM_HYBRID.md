# 🚀 Panduan Integrasi Downstream: Model Hybrid (SSO SiPintu + Login Manual)

Dokumen ini adalah panduan teknis implementasi untuk aplikasi downstream (aplikasi anak / klien seperti CBT, Absensi, Perpustakaan, dll) yang **sudah memiliki form login dan database siswa tersendiri**, tetapi ingin terhubung dengan **SiPintu Identity Gateway**.

---

## 📌 Konsep Model Hybrid

Aplikasi downstream mendukung **2 jalur login sekaligus** tanpa saling bentrok:

```text
[Jalur 1: SSO Terpusat]
Siswa Login di SiPintu ──▶ Klik Ikon Aplikasi di SiPintu ──▶ Langsung Masuk Dashboard Downstream
                                                              (Tanpa Form Login & Tanpa Ketik Password)

[Jalur 2: Login Manual Mandiri]
Siswa Buka Web Downstream ──▶ Ketik NIS & Password di Form ──▶ Masuk Dashboard Downstream
                                                              (Verifikasi Database Lokal, Tanpa SiPintu)
```

### Keunggulan Model Ini:
1. **Tidak Butuh Tombol Tambahan**: Tidak perlu menambahkan tombol *"Login dengan SiPintu"* pada form login downstream.
2. **Form Login Lama Tetap Utuh**: Kode halaman login yang sudah Anda deploy tidak perlu diubah atau dihapus.
3. **Fail-safe (Tahan Gangguan)**: Jika server SiPintu sedang maintenance, siswa tetap bisa login manual lewat Jalur 2.

---

## 🛠️ Langkah Integrasi di Aplikasi Downstream

Hanya ada **3 langkah cepat** di aplikasi downstream (contoh berbasis Laravel):

### Langkah 1: Pasang Kredensial di `.env` Downstream
Dapatkan `client_id` dan `client_secret` dari SiPintu (lihat [Langkah Registrasi](#-langkah-registrasi-di-sipintu)), lalu tambahkan ke file `.env` downstream:

```env
# Konfigurasi Koneksi SiPintu SSO
SIPINTU_BASE_URL=http://localhost:8000
SIPINTU_CLIENT_ID=app_xxxxxxxxxxxx
SIPINTU_CLIENT_SECRET=sec_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
SIPINTU_REDIRECT_URI=http://localhost:8001/oauth/callback
```
> ⚠️ **Catatan Port / Domain**: Sesuaikan `SIPINTU_BASE_URL` dan `SIPINTU_REDIRECT_URI` dengan domain/port server Anda.

---

### Langkah 2: Tambahkan 1 Route di `routes/web.php` Downstream
Form login lama tetap dibiarkan ada. Anda cukup menambahkan **1 baris route** untuk menangkap lemparan dari SiPintu:

```php
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\AuthController; // Controller login manual Anda

// =========================================================================
// JALUR 2: FORM LOGIN MANUAL (KODE LAMA ANDA - JANGAN DIHAPUS)
// =========================================================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================================
// JALUR 1: CALLBACK SSO SIPINTU (BARU)
// =========================================================================
Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
```

---

### Langkah 3: Buat `OAuthController.php` di Downstream
Buat file baru di `app/Http/Controllers/OAuthController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class OAuthController extends Controller
{
    /**
     * Menerima kiriman pengguna dari Portal SiPintu
     */
    public function callback(Request $request)
    {
        // 1. Tangkap kode otorisasi dari SiPintu
        $code = $request->input('code');

        if (! $code) {
            return redirect()->route('login')->with('error', 'Otorisasi dari SiPintu gagal.');
        }

        $baseUrl = rtrim(env('SIPINTU_BASE_URL'), '/');

        // 2. Tukar kode dengan Access Token (Server-to-Server)
        $tokenResponse = Http::asForm()->acceptJson()->post("{$baseUrl}/oauth/token", [
            'grant_type'    => 'authorization_code',
            'client_id'     => env('SIPINTU_CLIENT_ID'),
            'client_secret' => env('SIPINTU_CLIENT_SECRET'),
            'redirect_uri'  => env('SIPINTU_REDIRECT_URI'),
            'code'          => $code,
        ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('login')->with('error', 'Gagal memverifikasi token ke SiPintu.');
        }

        $accessToken = $tokenResponse->json('access_token');

        // 3. Ambil data profil siswa dari endpoint SiPintu
        $userResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/api/v1/user");

        if ($userResponse->failed()) {
            return redirect()->route('login')->with('error', 'Gagal mengambil data akun dari SiPintu.');
        }

        $sipintuUser = $userResponse->json('data') ?? $userResponse->json();

        // 4. Cocokkan dengan data siswa yang SUDAH ADA di database lokal downstream
        //    (Bisa menggunakan NIS / external_id atau Email)
        $user = User::where('nis', $sipintuUser['external_id'])
            ->orWhere('email', $sipintuUser['email'])
            ->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Akun siswa tidak terdaftar pada aplikasi ini.');
        }

        // 5. Autentikasikan sesi lokal & arahkan ke dashboard
        Auth::login($user, true);

        return redirect()->intended('/dashboard')->with('success', "Selamat datang, {$user->name}!");
    }
}
```

---

## 🔑 Langkah Registrasi di SiPintu

Agar SiPintu mengenali aplikasi downstream Anda, daftarkan aplikasi melalui terminal di folder `SiPintu`:

```bash
php artisan sipintu:sso-client "Nama Aplikasi Anda" --redirect=http://localhost:8001/oauth/callback --base-url=http://localhost:8001
```

Perintah ini akan mencetak:
* **Client ID** (`app_...`)
* **Client Secret** (`sec_...`)

Salin kedua nilai tersebut ke file `.env` downstream Anda di [Langkah 1](#langkah-1-pasang-kredensial-di-env-downstream).

---

## 🧪 Alur Pengujian & Pembuktian

### Uji Coba Jalur 1 (SSO via Portal SiPintu):
1. Buka browser, akses portal SiPintu (`http://localhost:8000/login`).
2. Login sebagai Siswa.
3. Di dashboard SiPintu, klik kartu/ikon aplikasi downstream Anda.
4. **Hasil**: Browser langsung membuka dashboard aplikasi downstream (`http://localhost:8001/dashboard`) dalam keadaan sudah login tanpa melewati form login.

### Uji Coba Jalur 2 (Login Manual):
1. Buka browser baru / tab incognito.
2. Langsung akses halaman login downstream (`http://localhost:8001/login`).
3. Masukkan NIS dan Password lokal siswa.
4. Klik **Login**.
5. **Hasil**: Siswa berhasil masuk dashboard downstream secara mandiri tanpa terhubung ke SiPintu.

---

## ❓ FAQ & Troubleshooting

| Pertanyaan / Kendala | Solusi |
| :--- | :--- |
| **Apakah form login lama perlu diubah?** | Tidak perlu sama sekali. Form login lama tetap bekerja 100% untuk Jalur 2. |
| **Apakah butuh tombol "Login SiPintu" di downstream?** | Tidak perlu. Siswa mengakses SSO langsung dari dashboard/portal SiPintu. |
| **Error: `Akun siswa tidak terdaftar`** | Pastikan data siswa sudah disinkronkan ke database downstream sehingga query `where('nis', ...)` menemukan datanya. |
| **Error: `invalid_client` saat tukar token** | Periksa kembali `SIPINTU_CLIENT_ID` dan `SIPINTU_CLIENT_SECRET` di `.env` downstream, pastikan sama persis dengan yang ada di database SiPintu. |
